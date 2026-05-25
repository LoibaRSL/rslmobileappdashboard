<?php
// app/Services/Business/ExternalSoapAmendmentService.php

namespace App\Services\Business\amend;

use App\Models\BusinessAmendment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalSoapService
{
    protected string $soapUrl;
    protected string $username;
    protected string $password;
    protected string $soapAction;
    protected bool $mockMode;

    public function __construct()
    {
        $this->soapUrl = config('services.business_soap.url', 'http://192.168.1.32:6500/ouaf/XAIApp/xaiserver/CMBUSEREG');
        $this->username = config('services.business_soap.username', 'USER22');
        $this->password = config('services.business_soap.password', 'password22');
        $this->soapAction = config('services.business_soap.action', 'http://ouaf.oracle.com/spl/XAIXapp/xaiserver/CMBUSEREG');
        $this->mockMode = config('services.business_soap.mock_mode', false);
    }

    public function sendBusinessAmendment(BusinessAmendment $amendment): array
    {
        if ($this->mockMode) {
            return $this->mockSoapCall($amendment);
        }

        try {
            // Load any relationships if they exist
            $amendment->load([
                'files',
                'histories',
            ]);

            // Build SOAP XML request from amendment_data
            $soapXml = $this->buildSoapRequest($amendment);

            Log::info('Business Amendment SOAP Request XML', ['xml' => $soapXml]);

            // Send SOAP request via HTTP
            $soapResponse = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => $this->soapAction,
            ])
            ->withBasicAuth($this->username, $this->password)
            ->timeout(60)
            ->withBody($soapXml, 'text/xml')
            ->post($this->soapUrl);

            // Log the response
            Log::info('Business Amendment SOAP Raw Response', [
                'amendment_id' => $amendment->id,
                'reference_number' => $amendment->reference_number,
                'original_tin' => $amendment->original_tin,
                'status' => $soapResponse->status(),
                'body' => $soapResponse->body(),
            ]);

            if ($soapResponse->failed()) {
                Log::error("Business Amendment SOAP Request Failed", [
                    'status' => $soapResponse->status(),
                    'body' => $soapResponse->body(),
                    'amendment_id' => $amendment->id,
                    'reference_number' => $amendment->reference_number,
                ]);

                throw new \Exception("SOAP request failed with status: {$soapResponse->status()}");
            }

            // Extract response data from SOAP response
            // Extract response data from SOAP response
            $responseData = $this->extractResponseData($soapResponse->body());

            if ($responseData['success']) {
                return [
                    'success' => true,
                    'tin' => $responseData['tin'],
                    'registration_form_id' => $responseData['registration_form_id'],
                    'resperson_id' => $responseData['resperson_id'],
                    'reference_id' => $amendment->reference_number,
                    'amendment_tin' => $amendment->amendment_tin,
                    'message' => $responseData['message'],
                    'raw_response' => $soapResponse->body(),
                ];
            } else {
                // Even though HTTP succeeded, business logic failed
                Log::error("Business Amendment SOAP Request - Business Logic Failed", [
                    'message' => $responseData['message'],
                    'amendment_id' => $amendment->id,
                    'reference_number' => $amendment->reference_number,
                    'original_tin' => $amendment->original_tin,
                    'raw_response' => $soapResponse->body(),
                ]);

                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'External system returned an error',
                    'raw_response' => $soapResponse->body(),
                ];
            }

        } catch (\Exception $e) {
            Log::error("Business Amendment SOAP Service Error", [
                'error' => $e->getMessage(),
                'amendment_id' => $amendment->id,
                'reference_number' => $amendment->reference_number,
                'original_tin' => $amendment->original_tin,
            ]);

            throw new \Exception("Business amendment SOAP service error: {$e->getMessage()}");
        }
    }

    protected function extractResponseData(string $soapResponse): array
{
    try {
        $xml = simplexml_load_string($soapResponse);
        
        if ($xml === false) {
            Log::error('Failed to parse business amendment SOAP response XML');
            return [
                'success' => false,
                'message' => 'Failed to parse SOAP response XML',
                'tin' => null,
                'registration_form_id' => null,
                'resperson_id' => null
            ];
        }

        // Register namespaces
        $xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml->registerXPathNamespace('cmb', 'http://oracle.com/CMBUSEREG.xsd');

        // Extract message first
        $message = null;
        $messageResult = $xml->xpath('//cmb:message');
        if (!empty($messageResult)) {
            $message = (string)$messageResult[0];
        }

        // Extract TIN
        $tin = null;
        $tinResult = $xml->xpath('//cmb:TIN');
        if (!empty($tinResult)) {
            $tin = (string)$tinResult[0];
        }

        // Extract registration form ID
        $registrationFormId = null;
        $formIdResult = $xml->xpath('//cmb:registrationFormId');
        if (!empty($formIdResult)) {
            $registrationFormId = (string)$formIdResult[0];
        }

        // Extract person ID
        $personId = null;
        $personIdResult = $xml->xpath('//cmb:personID');
        if (!empty($personIdResult)) {
            $personId = (string)$personIdResult[0];
        }

        Log::info('Business amendment response data extracted', [
            'message' => $message,
            'tin' => $tin,
            'registration_form_id' => $registrationFormId,
            'resperson_id' => $personId
        ]);

        // Check if this is a successful response
        $isSuccess = $message === 'Your registration amendment has successfully been processed';
        
        return [
            'success' => $isSuccess,
            'message' => $message,
            'tin' => $tin,
            'registration_form_id' => $registrationFormId,
            'resperson_id' => $personId
        ];

    } catch (\Exception $e) {
        Log::error('Error extracting data from business amendment SOAP response', [
            'error' => $e->getMessage(),
            'response_sample' => substr($soapResponse, 0, 500)
        ]);
        return [
            'success' => false,
            'message' => 'Error parsing response: ' . $e->getMessage(),
            'tin' => null,
            'registration_form_id' => null,
            'resperson_id' => null
        ];
    }
}
    protected function buildSoapRequest(BusinessAmendment $amendment): string
    {
        // Get amendment data from JSON field
        $amendmentData = $amendment->amendment_data ?? [];
        if (is_string($amendmentData)) {
            $amendmentData = json_decode($amendmentData, true) ?? [];
        }

        // Extract data from amendment_data
        $documentLocator = $amendmentData['document_locator'] ?? $amendment->document_locator;
        $receiveDate = $amendmentData['receive_date'] ?? $amendment->receive_date;
        $registrationType = $amendmentData['reg_type'] ?? $amendmentData['registration_type'] ?? 'AMND';
        $tin = $amendmentData['tin'] ?? $amendment->original_tin;
        $etpmTin = $amendmentData['etpm_tin'] ?? $amendment->amendment_tin ?? $amendment->original_tin;
        $effectiveDate = $amendmentData['effective_date'] ?? '';
        
        // Business details - CORRECTED MAPPING
        $businessDetails = $amendmentData['business_details'] ?? [];
        $businessType = $amendmentData['business_type'] ?? '';
        $isSoleTrader = $amendmentData['is_sole_trader'] ?? false;
        
        // Extract name from business details - CORRECTED
        $title = $businessDetails['title'] ?? '';
        $surname = $businessDetails['surname'] ?? '';
        $forename = $businessDetails['forename'] ?? '';
        $maidenName = $businessDetails['maiden_name'] ?? '';
        
        // Use legal_name directly from business_details
        $legalName = $businessDetails['legal_name'] ?? '';
        
        // If no legal_name, try to construct from name structure
        if (empty($legalName)) {
            $nameStructure = $businessDetails['name_structure'] ?? '';
            if (is_string($nameStructure)) {
                $legalName = $nameStructure;
            }
        }
        
        // Trade details - CORRECTED MAPPING
        $tradeDetails = $amendmentData['trade_details'] ?? [];
        $tradeDetails = $this->normaliseList($tradeDetails);
        
        // Contact info - CORRECTED MAPPING
        $contactInfo = $amendmentData['contact_info'] ?? [];
        $postalAddress = $contactInfo['postal_address'] ?? [];
        $physicalAddress = $contactInfo['physical_address'] ?? [];
        $email = $contactInfo['email'] ?? '';
        $phoneDetails = $contactInfo['phone_details'] ?? [];
        $structuredPhones = $contactInfo['structured_phones'] ?? [];
        
        // Accountant/Nominated Officer - CORRECTED MAPPING
        $accountantNominated = $amendmentData['accountant_nominated'] ?? [];
        
        // Directors/Partners - CORRECTED MAPPING
        $directorsPartners = $amendmentData['directors_partners'] ?? [];
        
        // Bank/Mobile Money - CORRECTED MAPPING
        $bankMobileMoney = $amendmentData['bank_mobile_money'] ?? [];
        $bankDetails = $bankMobileMoney['bank_details'] ?? $bankMobileMoney['bankDetails'] ?? [];
        $mobileMoneyDetails = $bankMobileMoney['mobile_money_details']
            ?? $bankMobileMoney['mobileMoneyDetails']
            ?? $bankMobileMoney['mobile_money']
            ?? $bankMobileMoney['mobileMoney']
            ?? [];
        
        // Tax registrations - CORRECTED MAPPING
        $taxRegistrations = $amendmentData['tax_registrations'] ?? [];
        $vatSection = $taxRegistrations['vat'] ?? [];
        $payeSection = $taxRegistrations['paye'] ?? [];
        $fbtSection = $taxRegistrations['fbt'] ?? [];
        $whtSection = $taxRegistrations['wht'] ?? [];
        $antlSection = $taxRegistrations['antl'] ?? [];
        $plasticLevySection = $taxRegistrations['plastic_levy'] ?? [];
        
        // For sole traders only
        $soleTraderDetails = $amendmentData['sole_trader_details'] ?? [];
        
        // Build sections with corrected data mappings
        $phoneDetailsXml = $this->buildPhoneDetailsSection($phoneDetails, $structuredPhones);
        $principalDetails = $amendmentData['principal_details'] ?? [];
        $principalDetailsXml = $this->buildPrincipalDetailsSection($principalDetails);
        $directorGroupXml = $this->buildDirectorSection($directorsPartners);
        $bankDetailsXml = $this->buildBankDetailsSection($this->normaliseList($bankDetails));
        $mobileDetailsXml = $this->buildMobileMoneyDetailsSection($this->normaliseList($mobileMoneyDetails));
        $employerListXml = $this->buildEmployerListSection([]); // For sole traders only
        
        // FBT and WHT details
        $fbtDetailsXml = $this->buildFbtDetailsSection($fbtSection['details'] ?? []);
        $whtTypeDetailsXml = $this->buildWhtTypeDetailsSection($whtSection['type_details'] ?? []);
        
        // Build contact address section
        $contactAddressXml = $this->buildContactAddressSection($postalAddress, $physicalAddress);
        
        // Build accountant section
        $accountantSectionXml = $this->buildAccountantSection($accountantNominated);
        
        // Build officer section (for non-sole traders)
        $officerSectionXml = '';
        if (!$isSoleTrader && !empty($accountantNominated['nominated_officer_name'])) {
            $officerSectionXml = $this->buildOfficerSection($accountantNominated);
        }
        
        // Build sole trader sections (only for sole traders)
        $soleIdResXml = '';
        $soleCorrespondenceXml = '';
        $soleMiscXml = '';
        if ($isSoleTrader) {
            $personalIdentification = $soleTraderDetails['personal_identification'] ?? [];
            $miscellaneous = $soleTraderDetails['miscellaneous'] ?? [];
            $structuredPhones = $soleTraderDetails['structured_phones'] ?? [];
            
            $soleIdResXml = $this->buildSoleIdResSection($personalIdentification);
            $soleCorrespondenceXml = $this->buildSoleCorrespondenceSection($postalAddress, $physicalAddress, $structuredPhones);
            $soleMiscXml = $this->buildSoleMiscSection($soleTraderDetails);
        }

        // Build the complete SOAP XML
        $soapXml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cmb="http://oracle.com/CMBUSEREG.xsd">
   <soapenv:Header/>
   <soapenv:Body>
      <cmb:CMBUSEREG dateTimeTagFormat="xsd:strict">
         <cmb:input>
            <cmb:documentLocator>' . $this->escapeXml($documentLocator) . '</cmb:documentLocator>
            <cmb:receiveDate>' . $receiveDate . '</cmb:receiveDate>
            <cmb:rsnForReg>
               <cmb:legacyTIN>
                  <cmb:asCurrent>' . $this->escapeXml($tin) . '</cmb:asCurrent>
               </cmb:legacyTIN>
               <cmb:etpmTIN>
                  <cmb:asCurrent>' . $this->escapeXml($etpmTin) . '</cmb:asCurrent>
               </cmb:etpmTIN>
               <cmb:regType>
                  <cmb:asCurrent>' . $this->escapeXml($registrationType) . '</cmb:asCurrent>
               </cmb:regType>
               <cmb:effectiveDate>
                  <cmb:asCurrent>' . $effectiveDate . '</cmb:asCurrent>
               </cmb:effectiveDate>
            </cmb:rsnForReg>
            <cmb:detailsSection>
               <cmb:businessType>
                  <cmb:asCurrent>' . $this->escapeXml($businessType) . '</cmb:asCurrent>
               </cmb:businessType>
               <cmb:otherBusinessType>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:otherBusinessType>
               <cmb:name>
                  <cmb:asCurrent>' . $this->escapeXml($legalName) . '</cmb:asCurrent>
               </cmb:name>
               <cmb:companyRegNumber>
                  <cmb:asCurrent>' . $this->escapeXml($businessDetails['registration_number'] ?? '') . '</cmb:asCurrent>
               </cmb:companyRegNumber>
               <cmb:title>
                  <cmb:asCurrent>' . $this->escapeXml($title) . '</cmb:asCurrent>
               </cmb:title>
               <cmb:surname>
                  <cmb:asCurrent>' . $this->escapeXml($surname) . '</cmb:asCurrent>
               </cmb:surname>
               <cmb:forname>
                  <cmb:asCurrent>' . $this->escapeXml($forename) . '</cmb:asCurrent>
               </cmb:forname>
               <cmb:maidenName>
                  <cmb:asCurrent>' . $this->escapeXml($maidenName) . '</cmb:asCurrent>
               </cmb:maidenName>
            </cmb:detailsSection>
            <cmb:tradenameSection>
               <cmb:tradeNameDetails>' . $this->buildTradeNameDetailsSection($tradeDetails, $legalName, $receiveDate, $amendment->reference_number) . '
               </cmb:tradeNameDetails>
            </cmb:tradenameSection>' .
            $principalDetailsXml . '
            <cmb:contactSection>' . $contactAddressXml . '
               <cmb:phoneDetails>' . $phoneDetailsXml . '
               </cmb:phoneDetails>
               <cmb:emailAddress>
                  <cmb:asCurrent>' . $this->escapeXml($email) . '</cmb:asCurrent>
               </cmb:emailAddress>
            </cmb:contactSection>' .
            $accountantSectionXml .
            $officerSectionXml . '
            <cmb:directorSection>
               <cmb:directorGroup>' . $directorGroupXml . '
               </cmb:directorGroup>
            </cmb:directorSection>
            <cmb:bank>
               <cmb:bankDetails>' . $bankDetailsXml . '
               </cmb:bankDetails>
            </cmb:bank>
            <cmb:mobile>
               <cmb:mobileDetails>' . $mobileDetailsXml . '
               </cmb:mobileDetails>
            </cmb:mobile>' .
            $soleIdResXml .
            $soleCorrespondenceXml .
            $soleMiscXml . '
            <cmb:vatSection>
               <cmb:vatEffectiveDate>
                  <cmb:asCurrent>' . $this->escapeXml($vatSection['vat_effective_date'] ?? '') . '</cmb:asCurrent>
               </cmb:vatEffectiveDate>
               <cmb:vatReason>
                  <cmb:asCurrent>' . $this->escapeXml($vatSection['vat_reason'] ?? '') . '</cmb:asCurrent>
               </cmb:vatReason>
               <cmb:vatNewOrAcquired>
                  <cmb:asCurrent>' . $this->escapeXml($vatSection['vat_new_or_acquired'] ?? '') . '</cmb:asCurrent>
               </cmb:vatNewOrAcquired>
               <cmb:vatNumber>
                  <cmb:asCurrent>' . $this->escapeXml($vatSection['vat_number'] ?? '') . '</cmb:asCurrent>
               </cmb:vatNumber>
            </cmb:vatSection>
            <cmb:vatPreviousSection>
               <cmb:vatPreviousName>
                  <cmb:asCurrent>' . $this->escapeXml($vatSection['vat_previous_name'] ?? '') . '</cmb:asCurrent>
               </cmb:vatPreviousName>
               <cmb:vatPreviousTIN>
                  <cmb:asCurrent>' . $this->escapeXml($vatSection['vat_previous_tin'] ?? '') . '</cmb:asCurrent>
               </cmb:vatPreviousTIN>
               <cmb:vatPerID>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:vatPerID>
               <cmb:vatPreviousAddress>
                  <cmb:asCurrent>' . $this->escapeXml($vatSection['vat_previous_address'] ?? '') . '</cmb:asCurrent>
               </cmb:vatPreviousAddress>
            </cmb:vatPreviousSection>
            <cmb:payeSection>
               <cmb:payeEffectiveDate>
                  <cmb:asCurrent>' . $this->escapeXml($payeSection['paye_effective_date'] ?? '') . '</cmb:asCurrent>
               </cmb:payeEffectiveDate>
               <cmb:employeeNumber>
                  <cmb:asCurrent>' . $this->escapeXml($payeSection['employee_number'] ?? '') . '</cmb:asCurrent>
               </cmb:employeeNumber>
               <cmb:minSalary>
                  <cmb:asCurrent>' . $this->escapeXml($payeSection['min_salary'] ?? '') . '</cmb:asCurrent>
               </cmb:minSalary>
               <cmb:maxSalary>
                  <cmb:asCurrent>' . $this->escapeXml($payeSection['max_salary'] ?? '') . '</cmb:asCurrent>
               </cmb:maxSalary>
            </cmb:payeSection>
            <cmb:fbtSection>
               <cmb:fbtEffectiveDate>
                  <cmb:asCurrent>' . $this->escapeXml($fbtSection['fbt_effective_date'] ?? '') . '</cmb:asCurrent>
               </cmb:fbtEffectiveDate>
               <cmb:fbtDetails>' . $fbtDetailsXml . '
               </cmb:fbtDetails>
            </cmb:fbtSection>
            <cmb:whtSection>
               <cmb:whtEffectiveDate>' . $this->escapeXml($whtSection['wht_effective_date'] ?? '') . '</cmb:whtEffectiveDate>
               <cmb:nonResSvcProvider>
                  <cmb:asCurrent>' . $this->escapeXml(($whtSection['register_for_wht'] ?? false) ? ($whtSection['non_res_service_provider'] ?? '') : '') . '</cmb:asCurrent>
               </cmb:nonResSvcProvider>
               <cmb:nonResServProdDesc>
                  <cmb:asCurrent>' . $this->escapeXml($whtSection['non_res_service_desc'] ?? '') . '</cmb:asCurrent>
               </cmb:nonResServProdDesc>
               <cmb:resContractors>
                  <cmb:asCurrent>' . $this->escapeXml(($whtSection['register_for_wht'] ?? false) ? ($whtSection['resident_contractors'] ?? '') : '') . '</cmb:asCurrent>
               </cmb:resContractors>
               <cmb:resContractorsDesc>
                  <cmb:asCurrent>' . $this->escapeXml($whtSection['resident_contractors_desc'] ?? '') . '</cmb:asCurrent>
               </cmb:resContractorsDesc>
               <cmb:whtTypeDetails>' . $whtTypeDetailsXml . '
               </cmb:whtTypeDetails>
            </cmb:whtSection>
            <cmb:antlSection>
               <cmb:antlEffectiveDate>
                  <cmb:asCurrent>' . $this->escapeXml($antlSection['antl_effective_date'] ?? '') . '</cmb:asCurrent>
               </cmb:antlEffectiveDate>
               <cmb:antlNumber>
                  <cmb:asCurrent>' . $this->escapeXml($antlSection['antl_number'] ?? '') . '</cmb:asCurrent>
               </cmb:antlNumber>
            </cmb:antlSection>
            <cmb:pLevySection>
               <cmb:pLevyEffectiveDate>
                  <cmb:asCurrent>' . $this->escapeXml($plasticLevySection['plastic_levy_effective_date'] ?? '') . '</cmb:asCurrent>
               </cmb:pLevyEffectiveDate>
               <cmb:pLevyNumber>
                  <cmb:asCurrent>' . $this->escapeXml($plasticLevySection['plastic_levy_number'] ?? '') . '</cmb:asCurrent>
               </cmb:pLevyNumber>
            </cmb:pLevySection>
            <cmb:sbtSection>
               <cmb:sbtEffectiveDate>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:sbtEffectiveDate>
            </cmb:sbtSection>
         </cmb:input>
         <cmb:output>
            <cmb:confirmation>
               <cmb:message></cmb:message>
               <cmb:registrationFormId></cmb:registrationFormId>
               <cmb:TIN></cmb:TIN>
               <cmb:personID></cmb:personID>
            </cmb:confirmation>
         </cmb:output>
      </cmb:CMBUSEREG>
   </soapenv:Body>
</soapenv:Envelope>';

        return $soapXml;
    }

    // The following helper methods remain similar but adapted for amendment data structure

    protected function buildPrincipalDetailsSection(array $principalDetails): string
    {
        if (empty($principalDetails)) {
            return '';
        }

        $principalDetailsXml = '<cmb:principalDetailSection>
               <cmb:principalDetails>';
        
        if (isset($principalDetails[0]) && is_array($principalDetails[0])) {
            foreach ($principalDetails as $principal) {
                $principalDetailsXml .= '
                  <cmb:principalDetailsList>
                     <cmb:affalName><cmb:asCurrent>' . $this->escapeXml($principal['trade_name'] ?? '') . '</cmb:asCurrent></cmb:affalName>
                     <cmb:affalVATNumber><cmb:asCurrent>' . $this->escapeXml($principal['vat_number'] ?? '') . '</cmb:asCurrent></cmb:affalVATNumber>
                     <cmb:affalCommission><cmb:asCurrent>' . $this->escapeXml($principal['commission'] ?? '') . '</cmb:asCurrent></cmb:affalCommission>
                     <cmb:affalcommencementDate><cmb:asCurrent>' . $this->escapeXml($principal['commencement_date'] ?? '') . '</cmb:asCurrent></cmb:affalcommencementDate>
                     <cmb:affalContractEndDate><cmb:asCurrent>' . $this->escapeXml($principal['contract_end_date'] ?? '') . '</cmb:asCurrent></cmb:affalContractEndDate>
                  </cmb:principalDetailsList>';
            }
        } else {
            $principalDetailsXml .= '
                  <cmb:principalDetailsList>
                     <cmb:affalName><cmb:asCurrent>' . $this->escapeXml($principalDetails['name'] ?? '') . '</cmb:asCurrent></cmb:affalName>
                     <cmb:affalVATNumber><cmb:asCurrent>' . $this->escapeXml($principalDetails['vat_number'] ?? '') . '</cmb:asCurrent></cmb:affalVATNumber>
                     <cmb:affalCommission><cmb:asCurrent>' . $this->escapeXml($principalDetails['commission'] ?? '') . '</cmb:asCurrent></cmb:affalCommission>
                     <cmb:affalcommencementDate><cmb:asCurrent>' . $this->escapeXml($principalDetails['commencement_date'] ?? '') . '</cmb:asCurrent></cmb:affalcommencementDate>
                     <cmb:affalContractEndDate><cmb:asCurrent>' . $this->escapeXml($principalDetails['contract_end_date'] ?? '') . '</cmb:asCurrent></cmb:affalContractEndDate>
                  </cmb:principalDetailsList>';
        }
        
        $principalDetailsXml .= '
               </cmb:principalDetails>
            </cmb:principalDetailSection>';
        
        return $principalDetailsXml;
    }

    protected function buildContactAddressSection(array $postalAddress, array $physicalAddress): string
    {
        $postCountry = $postalAddress['country'] ?? 'LS';
        $postAddress1 = $postalAddress['address1'] ?? '';
        $postAddress2 = $postalAddress['address2'] ?? '';
        $postCity = $postalAddress['city'] ?? '';
        $postCounty = $postalAddress['district'] ?? '';
        $postPostal = $postalAddress['postal_code'] ?? '';

        $phyCountry = $physicalAddress['country'] ?? 'LS';
        $phyAddress1 = $physicalAddress['address1'] ?? '';
        $phyAddress2 = $physicalAddress['address2'] ?? '';
        $phyCity = $physicalAddress['city'] ?? '';
        $phyCounty = $physicalAddress['district'] ?? '';
        $phyPostal = $physicalAddress['postal_code'] ?? '';

        return '
               <cmb:postCountry>
                  <cmb:asCurrent>' . $this->escapeXml($postCountry) . '</cmb:asCurrent>
               </cmb:postCountry>
               <cmb:postType>
                  <cmb:asCurrent>' . $this->escapeXml($postalAddress['postal_type'] ?? '') . '</cmb:asCurrent>
               </cmb:postType>
               <cmb:postNum>
                  <cmb:asCurrent>' . $this->escapeXml($postalAddress['postal_number'] ?? '') . '</cmb:asCurrent>
               </cmb:postNum>
               <cmb:postPostal>
                  <cmb:asCurrent>' . $this->escapeXml($postPostal) . '</cmb:asCurrent>
               </cmb:postPostal>
               <cmb:postAddress1>
                  <cmb:asCurrent>' . $this->escapeXml($postAddress1) . '</cmb:asCurrent>
               </cmb:postAddress1>
               <cmb:postAddress2>
                  <cmb:asCurrent>' . $this->escapeXml($postAddress2) . '</cmb:asCurrent>
               </cmb:postAddress2>
               <cmb:postAddress3>
                  <cmb:asCurrent>' . $this->escapeXml($postalAddress['address3'] ?? '') . '</cmb:asCurrent>
               </cmb:postAddress3>
               <cmb:postAddress4>
                  <cmb:asCurrent>' . $this->escapeXml($postalAddress['address4'] ?? '') . '</cmb:asCurrent>
               </cmb:postAddress4>
               <cmb:postCity>
                  <cmb:asCurrent>' . $this->escapeXml($postCity) . '</cmb:asCurrent>
               </cmb:postCity>
               <cmb:postCounty>
                  <cmb:asCurrent>' . $this->escapeXml($postCounty) . '</cmb:asCurrent>
               </cmb:postCounty>
               <cmb:phyCountry>
                  <cmb:asCurrent>' . $this->escapeXml($phyCountry) . '</cmb:asCurrent>
               </cmb:phyCountry>
               <cmb:phyAddress1>
                  <cmb:asCurrent>' . $this->escapeXml($phyAddress1) . '</cmb:asCurrent>
               </cmb:phyAddress1>
               <cmb:phyAddress2>
                  <cmb:asCurrent>' . $this->escapeXml($phyAddress2) . '</cmb:asCurrent>
               </cmb:phyAddress2>
               <cmb:phyAddress3>
                  <cmb:asCurrent>' . $this->escapeXml($physicalAddress['address3'] ?? '') . '</cmb:asCurrent>
               </cmb:phyAddress3>
               <cmb:phyAddress4>
                  <cmb:asCurrent>' . $this->escapeXml($physicalAddress['address4'] ?? '') . '</cmb:asCurrent>
               </cmb:phyAddress4>
               <cmb:phyCity>
                  <cmb:asCurrent>' . $this->escapeXml($phyCity) . '</cmb:asCurrent>
               </cmb:phyCity>
               <cmb:phyCounty>
                  <cmb:asCurrent>' . $this->escapeXml($phyCounty) . '</cmb:asCurrent>
               </cmb:phyCounty>
               <cmb:phyPostal>
                  <cmb:asCurrent>' . $this->escapeXml($phyPostal) . '</cmb:asCurrent>
               </cmb:phyPostal>';
    }

    protected function buildAccountantSection(array $accountantData): string
    {
        $accountantTin = $accountantData['accountant_tin'] ?? '';
        $accountantName = $accountantData['accountant_name'] ?? '';
        
        if (empty($accountantTin) && empty($accountantName)) {
            return '';
        }

        return '
            <cmb:accountantSection>
               <cmb:name>
                  <cmb:asCurrent>' . $this->escapeXml($accountantName) . '</cmb:asCurrent>
               </cmb:name>
               <cmb:TIN>
                  <cmb:asCurrent>' . $this->escapeXml($accountantTin) . '</cmb:asCurrent>
               </cmb:TIN>
               <cmb:PerID>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:PerID>
               <cmb:postCountry>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postCountry>
               <cmb:postType>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postType>
               <cmb:postNum>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postNum>
               <cmb:postPostal>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postPostal>
               <cmb:postAddress1>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postAddress1>
               <cmb:postAddress2>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postAddress2>
               <cmb:postAddress3>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postAddress3>
               <cmb:postAddress4>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postAddress4>
               <cmb:postCity>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postCity>
               <cmb:postCounty>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postCounty>
               <cmb:phyCountry>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyCountry>
               <cmb:phyAddress1>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyAddress1>
               <cmb:phyAddress2>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyAddress2>
               <cmb:phyAddress3>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyAddress3>
               <cmb:phyAddress4>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyAddress4>
               <cmb:phyCity>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyCity>
               <cmb:phyCounty>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyCounty>
               <cmb:phyPostal>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyPostal>
               <cmb:phoneDetails>
                  <cmb:phoneDetailsList>
                     <cmb:phoneType>
                        <cmb:asCurrent></cmb:asCurrent>
                     </cmb:phoneType>
                     <cmb:phoneCode>
                        <cmb:asCurrent></cmb:asCurrent>
                     </cmb:phoneCode>
                     <cmb:phoneNumber>
                        <cmb:asCurrent></cmb:asCurrent>
                     </cmb:phoneNumber>
                  </cmb:phoneDetailsList>
               </cmb:phoneDetails>
               <cmb:emailAddress>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:emailAddress>
            </cmb:accountantSection>';
    }

    protected function buildOfficerSection(array $officerData): string
    {
        $officerTin = $officerData['nominated_officer_tin'] ?? '';
        $officerName = $officerData['nominated_officer_name'] ?? '';
        
        if (empty($officerTin) && empty($officerName)) {
            return '';
        }

        return '
            <cmb:officerSection>
               <cmb:name>
                  <cmb:asCurrent>' . $this->escapeXml($officerName) . '</cmb:asCurrent>
               </cmb:name>
               <cmb:TIN>
                  <cmb:asCurrent>' . $this->escapeXml($officerTin) . '</cmb:asCurrent>
               </cmb:TIN>
               <cmb:PerID>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:PerID>
               <cmb:postCountry>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postCountry>
               <cmb:postType>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postType>
               <cmb:postNum>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postNum>
               <cmb:postPostal>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postPostal>
               <cmb:postAddress1>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postAddress1>
               <cmb:postAddress2>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postAddress2>
               <cmb:postAddress3>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postAddress3>
               <cmb:postAddress4>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postAddress4>
               <cmb:postCity>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postCity>
               <cmb:postCounty>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postCounty>
               <cmb:phyCountry>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyCountry>
               <cmb:phyAddress1>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyAddress1>
               <cmb:phyAddress2>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyAddress2>
               <cmb:phyAddress3>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyAddress3>
               <cmb:phyAddress4>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyAddress4>
               <cmb:phyCity>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyCity>
               <cmb:phyCounty>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyCounty>
               <cmb:phyPostal>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyPostal>
               <cmb:phoneDetails>
                  <cmb:phoneDetailsList>
                     <cmb:phoneType>
                        <cmb:asCurrent></cmb:asCurrent>
                     </cmb:phoneType>
                     <cmb:phoneCode>
                        <cmb:asCurrent></cmb:asCurrent>
                     </cmb:phoneCode>
                     <cmb:phoneNumber>
                        <cmb:asCurrent></cmb:asCurrent>
                     </cmb:phoneNumber>
                  </cmb:phoneDetailsList>
               </cmb:phoneDetails>
               <cmb:emailAddress>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:emailAddress>
            </cmb:officerSection>';
    }

    protected function buildSoleIdResSection(array $personalIdentification): string
    {
        if (empty($personalIdentification)) {
            return '';
        }

        $dateOfBirth = $personalIdentification['date_of_birth'] ?? '';
        $passportNumber = $personalIdentification['passport_number'] ?? $personalIdentification['lesotho_id_number'] ?? '';
        $countryOfIssue = $personalIdentification['country_of_issue'] ?? '';
        $otherId = $personalIdentification['other_id'] ?? '';
        $otherIdNumber = $personalIdentification['other_id_number'] ?? '';
        $otherCountryOfIssue = $personalIdentification['other_country_of_issue'] ?? '';
        $countryOfBirth = $personalIdentification['country_of_birth'] ?? '';
        $countryOfResidence = $personalIdentification['country_of_residence'] ?? '';
        $countryOfCitizenship = $personalIdentification['country_of_citizenship'] ?? '';
        
        $passportExpiryDate = $personalIdentification['passport_expiry_date'] ?? $personalIdentification['lesotho_id_expiry'] ?? '';
        $otherIdExpiryDate = $personalIdentification['other_id_expiry_date'] ?? '';

        return '
            <cmb:soleIdResSection>
               <cmb:dateOfBirth>
                  <cmb:asCurrent>' . $this->escapeXml($dateOfBirth) . '</cmb:asCurrent>
               </cmb:dateOfBirth>
               <cmb:passportNum>
                  <cmb:asCurrent>' . $this->escapeXml($passportNumber) . '</cmb:asCurrent>
               </cmb:passportNum>
               <cmb:passportExpiryDate>
                  <cmb:asCurrent>' . $this->escapeXml($passportExpiryDate) . '</cmb:asCurrent>
               </cmb:passportExpiryDate>
               <cmb:countryOfIssue>
                  <cmb:asCurrent>' . $this->escapeXml($countryOfIssue) . '</cmb:asCurrent>
               </cmb:countryOfIssue>
               <cmb:otherID>
                  <cmb:asCurrent>' . $this->escapeXml($otherId) . '</cmb:asCurrent>
               </cmb:otherID>
               <cmb:otherIDNumber>
                  <cmb:asCurrent>' . $this->escapeXml($otherIdNumber) . '</cmb:asCurrent>
               </cmb:otherIDNumber>
               <cmb:otherIDExpiryDate>
                  <cmb:asCurrent>' . $this->escapeXml($otherIdExpiryDate) . '</cmb:asCurrent>
               </cmb:otherIDExpiryDate>
               <cmb:otherCountryOfIssue>
                  <cmb:asCurrent>' . $this->escapeXml($otherCountryOfIssue) . '</cmb:asCurrent>
               </cmb:otherCountryOfIssue>
               <cmb:countryOfBirth>
                  <cmb:asCurrent>' . $this->escapeXml($countryOfBirth) . '</cmb:asCurrent>
               </cmb:countryOfBirth>
               <cmb:countryOfRes>
                  <cmb:asCurrent>' . $this->escapeXml($countryOfResidence) . '</cmb:asCurrent>
               </cmb:countryOfRes>
               <cmb:countryOfCit>
                  <cmb:asCurrent>' . $this->escapeXml($countryOfCitizenship) . '</cmb:asCurrent>
               </cmb:countryOfCit>
            </cmb:soleIdResSection>';
    }

    protected function buildSoleCorrespondenceSection(array $postalAddress, array $physicalAddress, array $phones): string
    {
        $postCountry = $postalAddress['country'] ?? '';
        $postAddress1 = $postalAddress['address1'] ?? '';
        $postAddress2 = $postalAddress['address2'] ?? '';
        $postCity = $postalAddress['city'] ?? '';
        $postCounty = $postalAddress['district'] ?? '';
        $postPostal = $postalAddress['postal_code'] ?? '';

        $phyCountry = $physicalAddress['country'] ?? '';
        $phyAddress1 = $physicalAddress['address1'] ?? '';
        $phyAddress2 = $physicalAddress['address2'] ?? '';
        $phyCity = $physicalAddress['city'] ?? '';
        $phyCounty = $physicalAddress['district'] ?? '';
        $phyPostal = $physicalAddress['postal_code'] ?? '';

        // Build phone details for sole trader
        $phoneXml = '';
        if (!empty($phones)) {
            foreach ($phones as $phone) {
                $phoneNumber = $phone['phoneNumber'] ?? $phone['phone_number'] ?? '';
                if (is_array($phoneNumber)) {
                    $phoneNumber = $phoneNumber['text'] ?? '';
                }

                if (!empty($phoneNumber)) {
                    $phoneXml .= '
                     <cmb:phoneDetailsList>
                        <cmb:phoneType>
                           <cmb:asCurrent>' . $this->escapeXml($phone['phoneType'] ?? $phone['phone_type'] ?? '') . '</cmb:asCurrent>
                        </cmb:phoneType>
                        <cmb:phoneCode>
                           <cmb:asCurrent>' . $this->escapeXml($phone['phoneCode'] ?? $phone['phone_code'] ?? '') . '</cmb:asCurrent>
                        </cmb:phoneCode>
                        <cmb:phoneNumber>
                           <cmb:asCurrent>' . $this->escapeXml($phoneNumber) . '</cmb:asCurrent>
                        </cmb:phoneNumber>
                     </cmb:phoneDetailsList>';
                }
            }
        }

        return '
            <cmb:soleCorrespondenceSection>
               <cmb:postCountry>
                  <cmb:asCurrent>' . $this->escapeXml($postCountry) . '</cmb:asCurrent>
               </cmb:postCountry>
               <cmb:postType>
                  <cmb:asCurrent>' . $this->escapeXml($postalAddress['postal_type'] ?? '') . '</cmb:asCurrent>
               </cmb:postType>
               <cmb:postNum>
                  <cmb:asCurrent>' . $this->escapeXml($postalAddress['postal_number'] ?? '') . '</cmb:asCurrent>
               </cmb:postNum>
               <cmb:postPostal>
                  <cmb:asCurrent>' . $this->escapeXml($postPostal) . '</cmb:asCurrent>
               </cmb:postPostal>
               <cmb:postAddress1>
                  <cmb:asCurrent>' . $this->escapeXml($postAddress1) . '</cmb:asCurrent>
               </cmb:postAddress1>
               <cmb:postAddress2>
                  <cmb:asCurrent>' . $this->escapeXml($postAddress2) . '</cmb:asCurrent>
               </cmb:postAddress2>
               <cmb:postAddress3>
                  <cmb:asCurrent>' . $this->escapeXml($postalAddress['address3'] ?? '') . '</cmb:asCurrent>
               </cmb:postAddress3>
               <cmb:postAddress4>
                  <cmb:asCurrent>' . $this->escapeXml($postalAddress['address4'] ?? '') . '</cmb:asCurrent>
               </cmb:postAddress4>
               <cmb:postCity>
                  <cmb:asCurrent>' . $this->escapeXml($postCity) . '</cmb:asCurrent>
               </cmb:postCity>
               <cmb:postCounty>
                  <cmb:asCurrent>' . $this->escapeXml($postCounty) . '</cmb:asCurrent>
               </cmb:postCounty>
               <cmb:phyCountry>
                  <cmb:asCurrent>' . $this->escapeXml($phyCountry) . '</cmb:asCurrent>
               </cmb:phyCountry>
               <cmb:phyAddress1>
                  <cmb:asCurrent>' . $this->escapeXml($phyAddress1) . '</cmb:asCurrent>
               </cmb:phyAddress1>
               <cmb:phyAddress2>
                  <cmb:asCurrent>' . $this->escapeXml($phyAddress2) . '</cmb:asCurrent>
               </cmb:phyAddress2>
               <cmb:phyAddress3>
                  <cmb:asCurrent>' . $this->escapeXml($physicalAddress['address3'] ?? '') . '</cmb:asCurrent>
               </cmb:phyAddress3>
               <cmb:phyAddress4>
                  <cmb:asCurrent>' . $this->escapeXml($physicalAddress['address4'] ?? '') . '</cmb:asCurrent>
               </cmb:phyAddress4>
               <cmb:phyCity>
                  <cmb:asCurrent>' . $this->escapeXml($phyCity) . '</cmb:asCurrent>
               </cmb:phyCity>
               <cmb:phyCounty>
                  <cmb:asCurrent>' . $this->escapeXml($phyCounty) . '</cmb:asCurrent>
               </cmb:phyCounty>
               <cmb:phyPostal>
                  <cmb:asCurrent>' . $this->escapeXml($phyPostal) . '</cmb:asCurrent>
               </cmb:phyPostal>
               <cmb:phoneDetails>' . $phoneXml . '
               </cmb:phoneDetails>
               <cmb:emailAddress>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:emailAddress>
            </cmb:soleCorrespondenceSection>';
    }

    protected function buildSoleMiscSection(array $soleTraderDetails): string
    {
        $employerListXml = $this->buildEmployerListSection($soleTraderDetails);
        $miscellaneous = $soleTraderDetails['miscellaneous'] ?? [];

        $maritalStatus = $miscellaneous['marital_status'] ?? '';
        $marriageCondition = $miscellaneous['marriage_condition'] ?? '';
        $spouseTin = $miscellaneous['spouse_tin'] ?? '';
        $spouseName = $miscellaneous['spouse_name'] ?? '';
        $spouseMaidenName = $miscellaneous['spouse_maiden_name'] ?? '';
        $spousePerID = $miscellaneous['spouse_per_id'] ?? '';

        return '
            <cmb:soleMiscSection>
               ' . $employerListXml . '
               <cmb:maritalStatus>
                  <cmb:asCurrent>' . $this->escapeXml($maritalStatus) . '</cmb:asCurrent>
               </cmb:maritalStatus>
               <cmb:condMarriage>
                  <cmb:asCurrent>' . $this->escapeXml($marriageCondition) . '</cmb:asCurrent>
               </cmb:condMarriage>
               <cmb:spouseTIN>
                  <cmb:asCurrent>' . $this->escapeXml($spouseTin) . '</cmb:asCurrent>
               </cmb:spouseTIN>
               <cmb:spouseName>
                  <cmb:asCurrent>' . $this->escapeXml($spouseName) . '</cmb:asCurrent>
               </cmb:spouseName>
               <cmb:spouseMaiden>
                  <cmb:asCurrent>' . $this->escapeXml($spouseMaidenName) . '</cmb:asCurrent>
               </cmb:spouseMaiden>
               <cmb:spousePerID>
                  <cmb:asCurrent>' . $this->escapeXml($spousePerID) . '</cmb:asCurrent>
               </cmb:spousePerID>
            </cmb:soleMiscSection>';
    }

    protected function buildEmployerListSection(array $soleTraderDetails): string
    {
        $employerListXml = '<cmb:employerList>';
        
        if (!empty($soleTraderDetails['employerList'])) {
            $employers = $soleTraderDetails['employerList'];
            if (is_array($employers)) {
                foreach ($employers as $employer) {
                    $employerListXml .= '
                     <cmb:employerListList>
                        <cmb:employer>
                           <cmb:asCurrent>' . $this->escapeXml($employer) . '</cmb:asCurrent>
                        </cmb:employer>
                     </cmb:employerListList>';
                }
            }
        }
        
        $employerListXml .= '
               </cmb:employerList>';
        
        return $employerListXml;
    }

    protected function buildFbtDetailsSection(array $fbtDetails): string
    {
        $fbtDetailsXml = '';
        
        if (!empty($fbtDetails)) {
            foreach ($fbtDetails as $fbt) {
                $fbtDetailsXml .= '
                  <cmb:fbtDetailsList>
                     <cmb:fbtType>
                        <cmb:asCurrent>' . $this->escapeXml($fbt['type'] ?? '') . '</cmb:asCurrent>
                     </cmb:fbtType>
                  </cmb:fbtDetailsList>';
            }
        }
        
        return $fbtDetailsXml;
    }

    protected function buildWhtTypeDetailsSection(array $whtTypeDetails): string
    {
        $whtTypeDetailsXml = '';
        
        if (!empty($whtTypeDetails)) {
            foreach ($whtTypeDetails as $wht) {
                $whtTypeDetailsXml .= '
                  <cmb:whtTypeDetailsList>
                     <cmb:whtType>
                        <cmb:asCurrent>' . $this->escapeXml($wht['type'] ?? '') . '</cmb:asCurrent>
                     </cmb:whtType>
                     <cmb:whtOther>
                        <cmb:asCurrent>' . $this->escapeXml($wht['other'] ?? '') . '</cmb:asCurrent>
                     </cmb:whtOther>
                  </cmb:whtTypeDetailsList>';
            }
        }
        
        return $whtTypeDetailsXml;
    }

    protected function buildPhoneDetailsSection(array $phoneDetails, array $structuredPhones): string
    {
        $phoneDetailsXml = '';
        
        if (empty($phoneDetails) && !empty($structuredPhones)) {
            $phoneDetails = $structuredPhones;
        }

        if (!empty($phoneDetails)) {
            foreach ($phoneDetails as $phone) {
                $phoneNumber = $phone['phone_number'] ?? $phone['phoneNumber'] ?? '';
                if (is_array($phoneNumber)) {
                    $phoneNumber = $phoneNumber['text'] ?? '';
                }

                if (!empty($phoneNumber)) {
                    $phoneDetailsXml .= '
                  <cmb:phoneDetailsList>
                     <cmb:phoneType><cmb:asCurrent>' . $this->escapeXml($phone['phone_type'] ?? $phone['phoneType'] ?? 'OFC') . '</cmb:asCurrent></cmb:phoneType>
                     <cmb:phoneCode><cmb:asCurrent>' . $this->escapeXml($phone['phone_code'] ?? $phone['phoneCode'] ?? '266') . '</cmb:asCurrent></cmb:phoneCode>
                     <cmb:phoneNumber><cmb:asCurrent>' . $this->escapeXml($phoneNumber) . '</cmb:asCurrent></cmb:phoneNumber>
                  </cmb:phoneDetailsList>';
                }
            }
        }
        
        if (empty($phoneDetailsXml)) {
            $phoneDetailsXml .= '
                  <cmb:phoneDetailsList>
                     <cmb:phoneType><cmb:asCurrent>CEL1</cmb:asCurrent></cmb:phoneType>
                     <cmb:phoneCode><cmb:asCurrent>266</cmb:asCurrent></cmb:phoneCode>
                     <cmb:phoneNumber><cmb:asCurrent>0000000</cmb:asCurrent></cmb:phoneNumber>
                  </cmb:phoneDetailsList>';
        }

        return $phoneDetailsXml;
    }

    protected function buildDirectorSection(array $directorsPartners): string
    {
        $directorGroupXml = '';
        
        if (!empty($directorsPartners)) {
            foreach ($directorsPartners as $director) {
                $directorGroupXml .= '
                  <cmb:directorGroupList>
                     <cmb:directorName><cmb:asCurrent>' . $this->escapeXml($director['name'] ?? '') . '</cmb:asCurrent></cmb:directorName>
                     <cmb:directorTIN><cmb:asCurrent>' . $this->escapeXml($director['tin'] ?? '') . '</cmb:asCurrent></cmb:directorTIN>
                     <cmb:directorPerID><cmb:asCurrent></cmb:asCurrent></cmb:directorPerID>
                  </cmb:directorGroupList>';
            }
        } else {
            $directorGroupXml .= '
                  <cmb:directorGroupList>
                     <cmb:directorName><cmb:asCurrent></cmb:asCurrent></cmb:directorName>
                     <cmb:directorTIN><cmb:asCurrent></cmb:asCurrent></cmb:directorTIN>
                     <cmb:directorPerID><cmb:asCurrent></cmb:asCurrent></cmb:directorPerID>
                  </cmb:directorGroupList>';
        }

        return $directorGroupXml;
    }

    protected function buildBankDetailsSection(array $bankDetails): string
    {
        $bankDetailsXml = '';
        
        if (!empty($bankDetails)) {
            foreach ($bankDetails as $bank) {
                $bankDetailsXml .= '
                  <cmb:bankDetailsList>
                     <cmb:accountName><cmb:asCurrent>' . $this->escapeXml($bank['account_holder'] ?? '') . '</cmb:asCurrent></cmb:accountName>
                     <cmb:bank><cmb:asCurrent>' . $this->escapeXml($bank['bank_name'] ?? '') . '</cmb:asCurrent></cmb:bank>
                     <cmb:branch><cmb:asCurrent>' . $this->escapeXml($bank['branch'] ?? '') . '</cmb:asCurrent></cmb:branch>
                     <cmb:bankCountry><cmb:asCurrent>' . $this->escapeXml($bank['country'] ?? '') . '</cmb:asCurrent></cmb:bankCountry>
                     <cmb:bankAccountNum><cmb:asCurrent>' . $this->escapeXml($bank['account_number'] ?? '') . '</cmb:asCurrent></cmb:bankAccountNum>
                     <cmb:bankAccountType><cmb:asCurrent>' . $this->escapeXml($bank['account_type'] ?? '') . '</cmb:asCurrent></cmb:bankAccountType>
                     <cmb:swiftCode><cmb:asCurrent>' . $this->escapeXml($bank['swift_code'] ?? '') . '</cmb:asCurrent></cmb:swiftCode>
                     <cmb:branchCode><cmb:asCurrent>' . $this->escapeXml($bank['branch_code'] ?? '') . '</cmb:asCurrent></cmb:branchCode>
                     <cmb:accountAutoPayId><cmb:asCurrent>' . $this->escapeXml($bank['account_auto_pay_id'] ?? '') . '</cmb:asCurrent></cmb:accountAutoPayId>
                  </cmb:bankDetailsList>';
            }
        } else {
            $bankDetailsXml .= '
                  <cmb:bankDetailsList>
                     <cmb:accountName><cmb:asCurrent></cmb:asCurrent></cmb:accountName>
                     <cmb:bank><cmb:asCurrent></cmb:asCurrent></cmb:bank>
                     <cmb:branch><cmb:asCurrent></cmb:asCurrent></cmb:branch>
                     <cmb:bankCountry><cmb:asCurrent></cmb:asCurrent></cmb:bankCountry>
                     <cmb:bankAccountNum><cmb:asCurrent></cmb:asCurrent></cmb:bankAccountNum>
                     <cmb:bankAccountType><cmb:asCurrent></cmb:asCurrent></cmb:bankAccountType>
                     <cmb:swiftCode><cmb:asCurrent></cmb:asCurrent></cmb:swiftCode>
                     <cmb:branchCode><cmb:asCurrent></cmb:asCurrent></cmb:branchCode>
                     <cmb:accountAutoPayId><cmb:asCurrent></cmb:asCurrent></cmb:accountAutoPayId>
                  </cmb:bankDetailsList>';
        }

        return $bankDetailsXml;
    }

    protected function buildMobileMoneyDetailsSection(array $mobileMoneyDetails): string
    {
        $mobileDetailsXml = '';
        
        if (!empty($mobileMoneyDetails)) {
            foreach ($mobileMoneyDetails as $mobile) {
                $mobileDetailsXml .= '
                  <cmb:mobileDetailsList>
                     <cmb:mobileMoney><cmb:asCurrent>' . $this->escapeXml($mobile['mobile_money_type'] ?? $mobile['mobileMoneyType'] ?? $mobile['mobileMoney'] ?? '') . '</cmb:asCurrent></cmb:mobileMoney>
                     <cmb:mobileMoneyNumber><cmb:asCurrent>' . $this->escapeXml($mobile['mobile_number'] ?? $mobile['mobile_money_number'] ?? $mobile['mobileMoneyNumber'] ?? $mobile['number'] ?? '') . '</cmb:asCurrent></cmb:mobileMoneyNumber>
                     <cmb:accountAutoPayId><cmb:asCurrent>' . $this->escapeXml($mobile['account_auto_pay_id'] ?? '') . '</cmb:asCurrent></cmb:accountAutoPayId>
                  </cmb:mobileDetailsList>';
            }
        } else {
            $mobileDetailsXml .= '
                  <cmb:mobileDetailsList>
                     <cmb:mobileMoney><cmb:asCurrent></cmb:asCurrent></cmb:mobileMoney>
                     <cmb:mobileMoneyNumber><cmb:asCurrent></cmb:asCurrent></cmb:mobileMoneyNumber>
                     <cmb:accountAutoPayId><cmb:asCurrent></cmb:asCurrent></cmb:accountAutoPayId>
                  </cmb:mobileDetailsList>';
        }

        return $mobileDetailsXml;
    }

    protected function buildTradeNameDetailsSection(array $tradeDetails, ?string $fallbackName, string $fallbackDate, string $fallbackTraderNumber): string
    {
        $tradeDetails = $this->normaliseList($tradeDetails);

        if (empty($tradeDetails)) {
            $tradeDetails = [[
                'trade_name' => $fallbackName,
                'commencement_date' => $fallbackDate,
                'trader_number' => $fallbackTraderNumber,
            ]];
        }

        $xml = '';
        foreach ($tradeDetails as $trade) {
            $xml .= '
                  <cmb:tradeNameDetailsList>
                     <cmb:tradeName>
                        <cmb:asCurrent>' . $this->escapeXml($trade['trade_name'] ?? $trade['tradeName'] ?? $fallbackName) . '</cmb:asCurrent>
                     </cmb:tradeName>
                     <cmb:natureOfBusiness>
                        <cmb:asCurrent>' . $this->escapeXml($trade['nature_of_business_code'] ?? $trade['natureOfBusinessCode'] ?? '') . '</cmb:asCurrent>
                     </cmb:natureOfBusiness>
                     <cmb:seatCapacity>
                        <cmb:asCurrent>' . $this->escapeXml($trade['seat_capacity'] ?? $trade['seatCapacity'] ?? '') . '</cmb:asCurrent>
                     </cmb:seatCapacity>
                     <cmb:vehiclereg>
                        <cmb:asCurrent>' . $this->escapeXml($trade['vehicle_reg'] ?? $trade['vehicleReg'] ?? '') . '</cmb:asCurrent>
                     </cmb:vehiclereg>
                     <cmb:businessActivity>
                        <cmb:asCurrent>' . $this->escapeXml($trade['business_activity'] ?? $trade['businessActivity'] ?? '') . '</cmb:asCurrent>
                     </cmb:businessActivity>
                     <cmb:sbtTurnOver>
                        <cmb:asCurrent>' . $this->escapeXml($trade['sbt_turnover'] ?? $trade['sbtTurnOver'] ?? '') . '</cmb:asCurrent>
                     </cmb:sbtTurnOver>
                     <cmb:commencementDate>
                        <cmb:asCurrent>' . $this->escapeXml($trade['commencement_date'] ?? $trade['commencementDate'] ?? $fallbackDate) . '</cmb:asCurrent>
                     </cmb:commencementDate>
                     <cmb:traderNumber>
                        <cmb:asCurrent>' . $this->escapeXml($trade['trader_number'] ?? $trade['traderNumber'] ?? $trade['licenseNumber'] ?? $fallbackTraderNumber) . '</cmb:asCurrent>
                     </cmb:traderNumber>
                  </cmb:tradeNameDetailsList>';
        }

        return $xml;
    }

    protected function normaliseList(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true) ?? [];
        }

        if (!is_array($value) || $value === []) {
            return [];
        }

        return array_is_list($value) ? $value : [$value];
    }

    protected function escapeXml($value): string
    {
        if (is_array($value)) {
            $value = '';
        }
        return htmlspecialchars($value ?? '', ENT_XML1, 'UTF-8');
    }

    
    protected function mockSoapCall(BusinessAmendment $amendment): array
    {
        // Simulate API delay
        sleep(2);
        
        $mockTin = $amendment->amendment_tin ?? $amendment->original_tin ?? 'AMEND' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $mockFormId = 'AMEND-FORM-' . $amendment->reference_number;
        $mockPersonId = 'AMEND-PERS-' . $amendment->reference_number;
        
        Log::info('Mock business amendment SOAP call executed', [
            'reference_number' => $amendment->reference_number,
            'original_tin' => $amendment->original_tin,
            'amendment_tin' => $amendment->amendment_tin,
            'mock_tin' => $mockTin,
            'mock_form_id' => $mockFormId,
            'mock_person_id' => $mockPersonId,
        ]);

        return [
            'success' => true,
            'tin' => $mockTin,
            'registration_form_id' => $mockFormId,
            'person_id' => $mockPersonId,
            'reference_id' => $amendment->reference_number,
            'amendment_tin' => $amendment->amendment_tin,
            'message' => 'Mock: Business amendment submitted successfully to external system',
            'mock' => true,
        ];
    }
}
