<?php

namespace App\Services;

use App\Models\RiitReturn;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RiitSoapSubmissionService
{
    public function submit(RiitReturn $return): array
    {
        $xml = $this->buildEnvelope($return);

        if (config('services.riit_soap.mock_mode')) {
            return [
                'success' => true,
                'message' => 'Mock RIIT SOAP submission successful',
                'request' => $xml,
                'response' => '<mock>Mock RIIT SOAP submission successful</mock>',
                'http_status' => 200,
            ];
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => '',
            ])
                ->withBasicAuth(config('services.riit_soap.username'), config('services.riit_soap.password'))
                ->timeout(30)
                ->withBody($xml, 'text/xml')
                ->post(config('services.riit_soap.url'));

            $message = $this->parseMessage($response->body());

            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? $message : 'SOAP submission failed with HTTP ' . $response->status(),
                'request' => $xml,
                'response' => $response->body(),
                'http_status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'request' => $xml,
                'response' => null,
                'http_status' => null,
            ];
        }
    }

    private function buildEnvelope(RiitReturn $return): string
    {
        $formData = $return->form_data ?? [];
        $taxFormFilingType = $return->is_amendment ? 'C1AM' : 'C1OR';
        $receiveDate = optional($return->receive_date)->format('Y-m-d') ?: now()->format('Y-m-d');
        $documentLocator = $return->document_locator ?: $this->generateDocumentLocator($return);

        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cmr="http://oracle.com/CMRIITSUBMISSION.xsd">'
            . '<soapenv:Header/><soapenv:Body><cmr:CMRIITSUBMISSION dateTimeTagFormat="xsd:strict"><cmr:input>'
            . '<cmr:taxFormFilingType>' . $taxFormFilingType . '</cmr:taxFormFilingType>'
            . '<cmr:receiveDate>' . $receiveDate . '</cmr:receiveDate>'
            . '<cmr:documentLocator>' . $this->escape($documentLocator) . '</cmr:documentLocator>'
            . '<cmr:taxpayerPersonID>' . $this->escape($return->person_id) . '</cmr:taxpayerPersonID>'
            . '<cmr:endDate>' . optional($return->period_end_date)->format('Y-m-d') . '</cmr:endDate>'
            . $this->taxpayerDetails($return)
            . $this->partAIncome($formData, $return->return_type === 'nil')
            . $this->partBPension($formData, $return->return_type === 'nil')
            . $this->taxRatesThresholds($formData, $return->return_type === 'nil')
            . $this->taxComputation($formData, $return->return_type === 'nil')
            . $this->declarantDetails($formData, $return)
            . '</cmr:input></cmr:CMRIITSUBMISSION></soapenv:Body></soapenv:Envelope>';
    }

    private function taxpayerDetails(RiitReturn $return): string
    {
        return '<cmr:taxpayerDetails>'
            . '<cmr:taxYearEnd><cmr:asCurrent>' . $this->escape($return->tax_year_end ?: optional($return->period_end_date)->format('Y')) . '</cmr:asCurrent></cmr:taxYearEnd>'
            . '<cmr:taxStartDate><cmr:asCurrent>' . optional($return->period_start_date)->format('Y-m-d') . '</cmr:asCurrent></cmr:taxStartDate>'
            . '<cmr:taxEndDate><cmr:asCurrent>' . optional($return->period_end_date)->format('Y-m-d') . '</cmr:asCurrent></cmr:taxEndDate>'
            . '</cmr:taxpayerDetails>';
    }

    private function partAIncome(array $data, bool $nil): string
    {
        $employerDetails = '';
        foreach (($nil ? [] : ($data['employers'] ?? [])) as $employer) {
            $grossIncome = $this->number($employer['grossIncome'] ?? 0);
            $p16Deduction = $this->number($employer['taxDeducted'] ?? 0);
            $employerDetails .= '<cmr:employerDetailsList>'
                . '<cmr:employerName><cmr:asCurrent>' . $this->escape($employer['name'] ?? '') . '</cmr:asCurrent></cmr:employerName>'
                . '<cmr:emplTIN><cmr:asCurrent>' . $this->escape($employer['tin'] ?? '') . '</cmr:asCurrent></cmr:emplTIN>'
                . '<cmr:emplFrom><cmr:asCurrent>' . $this->escape($employer['startDateText'] ?? '') . '</cmr:asCurrent></cmr:emplFrom>'
                . '<cmr:emplUntil><cmr:asCurrent>' . $this->escape($employer['endDateText'] ?? '') . '</cmr:asCurrent></cmr:emplUntil>'
                . '<cmr:emplP16Deduction><cmr:asCurrent>' . $p16Deduction . '</cmr:asCurrent></cmr:emplP16Deduction>'
                . '<cmr:grossIncome><cmr:asCurrent>' . $grossIncome . '</cmr:asCurrent><cmr:asReported>' . $grossIncome . '</cmr:asReported></cmr:grossIncome>'
                . '</cmr:employerDetailsList>';
        }

        return '<cmr:partAIncome><cmr:employerDetails>' . $employerDetails . '</cmr:employerDetails>'
            . $this->amountNode('totGrossEmpIncome', $nil ? 0 : ($data['totalGrossEmploymentIncome'] ?? 0))
            . '<cmr:periodsOfUnempl><cmr:periodsOfUnemplList><cmr:unemplFrom><cmr:asCurrent/></cmr:unemplFrom><cmr:emplUntil><cmr:asCurrent/></cmr:emplUntil></cmr:periodsOfUnemplList></cmr:periodsOfUnempl>'
            . $this->amountNode('travelExpenses', $nil ? 0 : ($data['travelExpenses'] ?? 0))
            . $this->amountNode('educationExpenses', $nil ? 0 : ($data['educationExpenses'] ?? 0))
            . $this->amountNode('bookExpenses', $nil ? 0 : ($data['bookExpenses'] ?? 0))
            . $this->amountNode('motorExpenses', $nil ? 0 : ($data['motorExpenses'] ?? 0))
            . $this->amountNode('homeOfficeExpenses', $nil ? 0 : ($data['homeOfficeExpenses'] ?? 0))
            . $this->amountNode('contributionExpenses', $nil ? 0 : ($data['contributionExpenses'] ?? 0))
            . $this->amountNode('donationsExpenses', $nil ? 0 : ($data['donationsExpenses'] ?? 0))
            . $this->amountNode('unreimbursedTotal', $nil ? 0 : ($data['totalEmploymentExpenses'] ?? 0))
            . $this->amountNode('chargeEmplInc', $nil ? 0 : ($data['chargeableEmploymentIncome'] ?? 0))
            . '</cmr:partAIncome>';
    }

    private function partBPension(array $data, bool $nil): string
    {
        $pensionDetails = '';
        foreach (($nil ? [] : ($data['pensionPayers'] ?? [])) as $payer) {
            $grossPension = $this->number($payer['grossIncome'] ?? 0);
            $pensionDetails .= '<cmr:pensionInfoLabelList>'
                . '<cmr:pensionPayerName><cmr:asCurrent>' . $this->escape($payer['name'] ?? '') . '</cmr:asCurrent></cmr:pensionPayerName>'
                . '<cmr:pensionPayerTIN><cmr:asCurrent>' . $this->escape($payer['tin'] ?? '') . '</cmr:asCurrent></cmr:pensionPayerTIN>'
                . '<cmr:pensionFrom><cmr:asCurrent>' . $this->escape($payer['startDateText'] ?? '') . '</cmr:asCurrent></cmr:pensionFrom>'
                . '<cmr:pensionUntil><cmr:asCurrent>' . $this->escape($payer['endDateText'] ?? '') . '</cmr:asCurrent></cmr:pensionUntil>'
                . '<cmr:grossPension><cmr:asCurrent>' . $grossPension . '</cmr:asCurrent><cmr:asReported>' . $grossPension . '</cmr:asReported></cmr:grossPension>'
                . '</cmr:pensionInfoLabelList>';
        }

        return '<cmr:partBPension><cmr:pensionInfoLabel>' . $pensionDetails . '</cmr:pensionInfoLabel>'
            . $this->amountNode('grossPensionTotal', $nil ? 0 : ($data['totalGrossPensionIncome'] ?? 0))
            . $this->amountNode('donationsPaid', $nil ? 0 : ($data['donationsPension'] ?? 0))
            . $this->amountNode('chargeablePension', $nil ? 0 : ($data['chargeablePensionIncome'] ?? 0))
            . '</cmr:partBPension>';
    }

    private function taxRatesThresholds(array $data, bool $nil): string
    {
        return '<cmr:taxRatesThresholds>'
            . '<cmr:minimumThreshold><cmr:asCurrent>' . $this->number($nil ? 0 : ($data['minimumThreshold'] ?? 0)) . '</cmr:asCurrent></cmr:minimumThreshold>'
            . '<cmr:lowerBand><cmr:asCurrent>' . $this->number($nil ? 0 : ($data['lowerBand'] ?? 20)) . '</cmr:asCurrent></cmr:lowerBand>'
            . '<cmr:higherBand><cmr:asCurrent>' . $this->number($nil ? 0 : ($data['higherBand'] ?? 30)) . '</cmr:asCurrent></cmr:higherBand>'
            . '<cmr:personalTaxCredit><cmr:asCurrent>' . $this->number($nil ? 0 : ($data['personalTaxCredit'] ?? 0)) . '</cmr:asCurrent></cmr:personalTaxCredit>'
            . '</cmr:taxRatesThresholds>';
    }

    private function taxComputation(array $data, bool $nil): string
    {
        $value = fn (string $key) => $nil ? 0 : ($data[$key] ?? 0);

        return '<cmr:taxComputation>'
            . $this->amountNode('chargeableaIncomeEmployement', $value('chargeableEmploymentIncome'))
            . $this->amountNode('chargeableaIncomePension', $value('chargeablePensionIncome'))
            . $this->amountNode('totalChargeableIncome', $value('totalChargeableIncome'))
            . $this->amountNode('amount22', $value('amount22'))
            . $this->amountNode('taxed22', $value('taxOnLowerBand'))
            . $this->amountNode('amount35', $value('amount35'))
            . $this->amountNode('taxed35', $value('taxOnHigherBand'))
            . $this->amountNode('totalTaxBeforeCredits', $value('totalTaxBeforeCredits'))
            . $this->amountNode('personalTaxCredit', $value('personalTaxCreditAmount'))
            . $this->amountNode('totalTaxPersonalTaxCredit', $value('totalTaxAfterCredit'))
            . $this->amountNode('incomeTaxDeductions', $value('totalTaxAlreadyPaid'))
            . $this->amountNode('taxDue', $value('taxDue'))
            . $this->amountNode('taxOverpaid', $value('taxOverpaid'))
            . '<cmr:repayClaim><cmr:asCurrent>' . (($data['claimRepayment'] ?? false) ? 'true' : 'false') . '</cmr:asCurrent></cmr:repayClaim>'
            . $this->amountNode('taxAssessment', $value('taxAssessment'))
            . '</cmr:taxComputation>';
    }

    private function declarantDetails(array $data, RiitReturn $return): string
    {
        $name = $return->declarant_name ?: ($data['declarantName'] ?? 'Taxpayer');

        return '<cmr:declarantDetails><cmr:declarantName><cmr:asCurrent>' . $this->escape($name) . '</cmr:asCurrent></cmr:declarantName></cmr:declarantDetails>';
    }

    private function amountNode(string $name, mixed $value): string
    {
        $amount = $this->number($value);

        return '<cmr:' . $name . '><cmr:asCurrent>' . $amount . '</cmr:asCurrent><cmr:asReported>' . $amount . '</cmr:asReported></cmr:' . $name . '>';
    }

    private function number(mixed $value): string
    {
        return preg_replace('/[^0-9.\-]/', '', (string) $value) ?: '0';
    }

    private function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1, 'UTF-8');
    }

    private function generateDocumentLocator(RiitReturn $return): string
    {
        return ($return->is_amendment ? 'RIITAMD' : ($return->return_type === 'nil' ? 'NI' : 'RIIT')) . now()->format('dMY');
    }

    private function parseMessage(string $response): string
    {
        if (preg_match('/<Message>(.*?)<\/Message>/s', $response, $matches)) {
            return trim(Str::of($matches[1])->squish()->toString());
        }

        return 'Return submitted successfully';
    }
}
