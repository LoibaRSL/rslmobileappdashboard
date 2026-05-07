<?php
// app/Services/ExternalSoapService.php

namespace App\Services;

use App\Models\TinRegistration;
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
        $this->soapUrl = config('services.external_soap.url', 'http://192.168.1.32:6500/ouaf/XAIApp/xaiserver/CMINDEREG');
        $this->username = config('services.external_soap.username', 'USER22');
        $this->password = config('services.external_soap.password', 'password22');
        $this->soapAction = config('services.external_soap.action', 'http://ouaf.oracle.com/spl/XAIXapp/xaiserver/CMINDEREG');
        $this->mockMode = config('services.external_soap.mock_mode', false);
    }

    public function sendTinRegistration(TinRegistration $registration): array
{
    if ($this->mockMode) {
        return $this->mockSoapCall($registration);
    }

    try {
        // Load relationships
        $registration->load([
            'phoneDetails',
            'bankingDetails', 
            'mobileMoneyDetails',
            'employers'
        ]);

        // Build SOAP XML request
        $soapXml = $this->buildSoapRequest($registration);

        Log::info('SOAP Request XML', ['xml' => $soapXml]);

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
        Log::info('SOAP Raw Response', [
            'registration_id' => $registration->id,
            'status' => $soapResponse->status(),
            'body' => $soapResponse->body(),
        ]);

        if ($soapResponse->failed()) {
            Log::error("SOAP Request Failed", [
                'status' => $soapResponse->status(),
                'body' => $soapResponse->body(),
                'registration_id' => $registration->id,
            ]);

            return [
                'success' => false,
                'tin' => null,
                'error_message' => 'HTTP request failed: ' . $soapResponse->status(),
                'message' => 'SOAP request failed',
                'raw_response' => $soapResponse->body(),
            ];
        }

        // Extract TIN and error message from SOAP response
        $extracted = $this->extractTINFromSOAP($soapResponse->body());
        $tin = $extracted['tin'];
        $errorMessage = $extracted['error_message'];

        // SIMPLE LOGIC: Success = we have a TIN, regardless of messages
        $success = !empty($tin);

        return [
            'success' => $success,
            'tin' => $tin,
            'reference_id' => $tin,
            'error_message' => $errorMessage,
            'message' => $errorMessage ?: ($success ? 'Registration submitted successfully' : 'Registration failed - no TIN generated'),
            'raw_response' => $soapResponse->body(),
        ];

    } catch (\Exception $e) {
        Log::error("SOAP Service Error", [
            'error' => $e->getMessage(),
            'registration_id' => $registration->id,
            'tin' => $registration->tin,
        ]);

        return [
            'success' => false,
            'tin' => null,
            'error_message' => $e->getMessage(),
            'message' => "SOAP service error: {$e->getMessage()}",
        ];
    }
}
    protected function buildSoapRequest(TinRegistration $registration): string
    {
        // Get primary phone details (first phone number)
        $primaryPhone = $registration->phoneDetails->first();
        
        // Get primary banking details (first bank account)
        $primaryBank = $registration->bankingDetails->first();
        
        // Get primary mobile money details (first mobile money account)
        $primaryMobile = $registration->mobileMoneyDetails->first();

        // Map your database fields to the SOAP request format
        $data = [
            'proofID' => 'TRUE',
            'legacyTIN' => $registration->legacy_tin ?? '',
            'documentLocator' => $registration->document_locator ?? '',
            'etpmTIN' => $registration->tin ?? '',
            'regType' => $registration->registration_type ?? 'NEW',
            'effectiveDate' => $registration->effective_date,
            'recieveDate' => $registration->created_at->format('Y-m-d'),
            'title' => $registration->title ?? '',
            'surname' => $registration->surname ?? '',
            'forname' => $registration->forenames ?? '', 
            'name' => $registration->printed_name ?? ($registration->forenames . ' ' . $registration->surname),
            'maidenName' => $registration->maiden_name ?? '',
            'dateOfBirth' => $registration->date_of_birth?->format('Y-m-d') ?? '',
            'passportNum' => $registration->lesotho_id_number ?? '',
            'passportExpiryDate' => $registration->lesotho_id_expiry?->format('Y-m-d') ?? '',
            'countryOfIssue' => $registration->country_of_issue ?? '',
            'otherID' => $registration->other_id_type ?? '',
            'otherIDNumber' => $registration->other_id_number ?? '',
            'driversExpiryDate' => $registration->other_id_expiry?->format('Y-m-d') ?? '',
            'otherCountryOfIssue' => $registration->country_of_issue ?? '',
            'countryOfBirth' => $registration->country_of_birth ?? '',
            'countryOfRes' => $registration->country_of_residence ?? '',
            'countryOfCit' => $registration->country_of_citizenship ?? '',
            'postCountry' => $registration->post_country ?? '',
            'postType' => $registration->post_type ?? '',
            'postNum' => $registration->post_number ?? '',
            'postPostal' => $registration->post_code ?? '',
            'postAddress1' => $registration->post_address1 ?? '',
            'postAddress2' => $registration->post_address2 ?? '',
            'postAddress3' => $registration->post_address3 ?? '',
            'postAddress4' => $registration->post_address4 ?? '',

            'postCity' => $registration->post_city ?? '',
            'postCounty' => $registration->post_district ?? '',
            'phyCountry' => $registration->physical_country ?? '',
            'phyAddress1' => $registration->street_name ?? '',
            'phyAddress2' => $registration->nearest_place ?? '',
            'phyAddress3' => $registration->village ?? '',
            'phyCity' => $registration->town ?? '',
            'phyCounty' => $registration->physical_district ?? '',
            'phyPostal' => $registration->phy_postal ?? '',
            'phoneType' => $primaryPhone->phone_type ?? '',
            'phoneCode' => $primaryPhone->phone_code ?? '',
            'phoneNumber' => $primaryPhone->phone_number ?? '',
            'email' => $registration->email ?? '',
            'maritalStatus' => $registration->marital_status ?? '',
            'condMarriage' => $registration->condition_of_marriage ?? '',
            'spouseTIN' => $registration->spouse_tin ?? '',
            'spouseName' => $registration->spouse_name ?? '',
            'spouseMaiden' => $registration->spouse_maiden_name ?? '',
            'spousePerID' => $registration->spouse_personal_id ?? '',
            'mobileMoney' => $primaryMobile->mobile_money_type ?? '',
            'mobileMoneyNumber' => $primaryMobile->mobile_money_number ?? '',
            'accountAutoPayId' => '', // Add this field if you have it
            
            // Banking details from relationship
            'bankName' => $primaryBank->bank_name ?? '',
            'bankCountry' => $primaryBank->bank_country ?? '',
            'accountHolderName' => $primaryBank->account_holder_name ?? '',
            'branch' => $primaryBank->branch ?? '',
            'accountNumber' => $primaryBank->account_number ?? '',
            'accountType' => $primaryBank->account_type ?? '',
            'swiftCode' => $primaryBank->swift_code ?? '',
            'branchCode' => $primaryBank->branch_code ?? '',
        ];

        // Build phone details section with multiple entries
        $phoneDetailsXml = '';
        foreach ($registration->phoneDetails as $phone) {
            $phoneDetailsXml .= '
                  <cmin:phoneDetailsList>
                     <cmin:phoneType><cmin:asCurrent>' . $this->escapeXml($phone->phone_type) . '</cmin:asCurrent></cmin:phoneType>
                     <cmin:phoneCode><cmin:asCurrent>' . $this->escapeXml($phone->phone_code) . '</cmin:asCurrent></cmin:phoneCode>
                     <cmin:phoneNumber><cmin:asCurrent>' . $this->escapeXml($phone->phone_number) . '</cmin:asCurrent></cmin:phoneNumber>
                  </cmin:phoneDetailsList>';
        }

        // Build employer list section with multiple entries
        $employerListXml = '';
        if ($registration->employers->count() > 0) {
            foreach ($registration->employers as $employer) {
                $employerListXml .= '
                  <cmin:employerListList>
                     <cmin:employer><cmin:asCurrent>' . $this->escapeXml($employer->employer_name) . '</cmin:asCurrent></cmin:employer>
                  </cmin:employerListList>';
            }
        } else {
            // Default employer if none provided
            $employerListXml = '
                  <cmin:employerListList>
                     <cmin:employer><cmin:asCurrent>none</cmin:asCurrent></cmin:employer>
                  </cmin:employerListList>';
        }

        // Build mobile money details section with multiple entries
        $mobileDetailsXml = '';
        foreach ($registration->mobileMoneyDetails as $mobile) {
            $mobileDetailsXml .= '
                  <cmin:mobileDetailsList>
                     <cmin:mobileMoney><cmin:asCurrent>' . $this->escapeXml($mobile->mobile_money_type) . '</cmin:asCurrent></cmin:mobileMoney>
                     <cmin:mobileMoneyNumber><cmin:asCurrent>' . $this->escapeXml($mobile->mobile_money_number) . '</cmin:asCurrent></cmin:mobileMoneyNumber>
                     <cmin:accountAutoPayId><cmin:asCurrent>' . $this->escapeXml($data['accountAutoPayId']) . '</cmin:asCurrent></cmin:accountAutoPayId>
                  </cmin:mobileDetailsList>';
        }

        // Build banking details section with multiple entries
        $bankDetailsXml = '';
        foreach ($registration->bankingDetails as $bank) {
            $bankDetailsXml .= '
                  <cmin:bankDetailsList>
                     <cmin:accountName><cmin:asCurrent>' . $this->escapeXml($bank->account_holder_name) . '</cmin:asCurrent></cmin:accountName>
                     <cmin:bank><cmin:asCurrent>' . $this->escapeXml($bank->bank_code) . '</cmin:asCurrent></cmin:bank>
                     <cmin:branch><cmin:asCurrent>' . $this->escapeXml($bank->branch) . '</cmin:asCurrent></cmin:branch>
                     <cmin:bankCountry><cmin:asCurrent>' . $this->escapeXml($bank->bank_country) . '</cmin:asCurrent></cmin:bankCountry>
                     <cmin:bankAccountNum><cmin:asCurrent>' . $this->escapeXml($bank->account_number) . '</cmin:asCurrent></cmin:bankAccountNum>
                     <cmin:bankAccountType><cmin:asCurrent>CURR</cmin:asCurrent></cmin:bankAccountType>
                     <cmin:swiftCode><cmin:asCurrent>' . $this->escapeXml($bank->swift_code) . '</cmin:asCurrent></cmin:swiftCode>
                     <cmin:branchCode><cmin:asCurrent>' . $this->escapeXml($bank->branch_code) . '</cmin:asCurrent></cmin:branchCode>
                     <cmin:accountAutoPayId><cmin:asCurrent>' . $this->escapeXml($data['accountAutoPayId']) . '</cmin:asCurrent></cmin:accountAutoPayId>
                  </cmin:bankDetailsList>';
        }

        // Build the SOAP XML with exact format
        $soapXml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cmin="http://oracle.com/CMINDEREG.xsd">
   <soapenv:Header/>
   <soapenv:Body>
      <cmin:CMINDEREG dateTimeTagFormat="xsd:strict">
         <cmin:input>
            <cmin:documentLocator>' . $this->escapeXml($data['documentLocator']) . '</cmin:documentLocator>
            <cmin:receiveDate>' . $this->escapeXml($data['recieveDate']) . '</cmin:receiveDate>
            <cmin:rsnForReg>
               <cmin:legacyTIN><cmin:asCurrent></cmin:asCurrent></cmin:legacyTIN>
               <cmin:etpmTIN><cmin:asCurrent>' . $this->escapeXml($data['etpmTIN']) . '</cmin:asCurrent></cmin:etpmTIN>
               <cmin:regType><cmin:asCurrent>' . $this->escapeXml($data['regType']) . '</cmin:asCurrent></cmin:regType>
               <cmin:effectiveDate><cmin:asCurrent>' . $this->escapeXml($data['effectiveDate']) . '</cmin:asCurrent></cmin:effectiveDate>
            </cmin:rsnForReg>

            <cmin:mainSection>
               <cmin:title><cmin:asCurrent>' . $this->escapeXml($data['title']) . '</cmin:asCurrent></cmin:title>
               <cmin:surname><cmin:asCurrent>' . $this->escapeXml($data['surname']) . '</cmin:asCurrent></cmin:surname>
               <cmin:forname><cmin:asCurrent>' . $this->escapeXml($data['forname']) . '</cmin:asCurrent></cmin:forname>
               <cmin:name><cmin:asCurrent>' . $this->escapeXml($data['name']) . '</cmin:asCurrent></cmin:name>
               <cmin:maidenName><cmin:asCurrent>' . $this->escapeXml($data['maidenName']) . '</cmin:asCurrent></cmin:maidenName>
            </cmin:mainSection>

            <cmin:idAndResidency>
               <cmin:proofID><cmin:asCurrent>' . $this->escapeXml($data['proofID']) . '</cmin:asCurrent></cmin:proofID>
               <cmin:dateOfBirth><cmin:asCurrent>' . $this->escapeXml($data['dateOfBirth']) . '</cmin:asCurrent></cmin:dateOfBirth>
               <cmin:passportNum><cmin:asCurrent>' . $this->escapeXml($data['passportNum']) . '</cmin:asCurrent></cmin:passportNum>
               <cmin:passportExpiryDate><cmin:asCurrent>' . $this->escapeXml($data['passportExpiryDate']) . '</cmin:asCurrent></cmin:passportExpiryDate>
               <cmin:countryOfIssue><cmin:asCurrent>' . $this->escapeXml($data['countryOfCit']) . '</cmin:asCurrent></cmin:countryOfIssue>
               <cmin:otherID><cmin:asCurrent>' . $this->escapeXml($data['otherID']) . '</cmin:asCurrent></cmin:otherID>
               <cmin:otherIDNumber><cmin:asCurrent>' . $this->escapeXml($data['otherIDNumber']) . '</cmin:asCurrent></cmin:otherIDNumber>
               <cmin:driversExpiryDate><cmin:asCurrent>' . $this->escapeXml($data['driversExpiryDate']) . '</cmin:asCurrent></cmin:driversExpiryDate>
               <cmin:otherCountryOfIssue><cmin:asCurrent>' . $this->escapeXml($data['otherCountryOfIssue']) . '</cmin:asCurrent></cmin:otherCountryOfIssue>
               <cmin:countryOfBirth><cmin:asCurrent>' . $this->escapeXml($data['countryOfBirth']) . '</cmin:asCurrent></cmin:countryOfBirth>
               <cmin:countryOfRes><cmin:asCurrent>' . $this->escapeXml($data['countryOfRes']) . '</cmin:asCurrent></cmin:countryOfRes>
               <cmin:countryOfCit><cmin:asCurrent>' . $this->escapeXml($data['countryOfCit']) . '</cmin:asCurrent></cmin:countryOfCit>
            </cmin:idAndResidency>

            <cmin:correspondence>
               <cmin:postCountry><cmin:asCurrent>' . $this->escapeXml($data['postCountry']) . '</cmin:asCurrent></cmin:postCountry>
               <cmin:postType><cmin:asCurrent>' . $this->escapeXml($data['postType']) . '</cmin:asCurrent></cmin:postType>
               <cmin:postNum><cmin:asCurrent>' . $this->escapeXml($data['postNum']) . '</cmin:asCurrent></cmin:postNum>
               <cmin:postPostal><cmin:asCurrent>' . $this->escapeXml($data['postPostal']) . '</cmin:asCurrent></cmin:postPostal>
               <cmin:postAddress1><cmin:asCurrent>' . $this->escapeXml($data['postAddress1']) . '</cmin:asCurrent></cmin:postAddress1>
               <cmin:postAddress2><cmin:asCurrent>' . $this->escapeXml($data['postAddress2']) . '</cmin:asCurrent></cmin:postAddress2>
               <cmin:postAddress3><cmin:asCurrent>' . $this->escapeXml($data['postAddress3']) . '</cmin:asCurrent></cmin:postAddress3>
               <cmin:postAddress4><cmin:asCurrent>' . $this->escapeXml($data['postAddress4']) . '</cmin:asCurrent></cmin:postAddress4>
               <cmin:postCity><cmin:asCurrent>' . $this->escapeXml($data['postCity']) . '</cmin:asCurrent></cmin:postCity>
               <cmin:postCounty><cmin:asCurrent>' . $this->escapeXml($data['postCounty']) . '</cmin:asCurrent></cmin:postCounty>
               <cmin:phyCountry><cmin:asCurrent>' . $this->escapeXml($data['phyCountry']) . '</cmin:asCurrent></cmin:phyCountry>
               <cmin:phyAddress1><cmin:asCurrent>' . $this->escapeXml($data['phyAddress1']) . '</cmin:asCurrent></cmin:phyAddress1>
                <cmin:phyAddress2><cmin:asCurrent>' . $this->escapeXml($data['phyAddress2']) . '</cmin:asCurrent></cmin:phyAddress2>
                <cmin:phyAddress3><cmin:asCurrent>' . $this->escapeXml($data['phyAddress3']) . '</cmin:asCurrent></cmin:phyAddress3>
               <cmin:phyCity><cmin:asCurrent>' . $this->escapeXml($data['phyCity']) . '</cmin:asCurrent></cmin:phyCity>
               <cmin:phyCounty><cmin:asCurrent>' . $this->escapeXml($data['phyCounty']) . '</cmin:asCurrent></cmin:phyCounty>
               <cmin:phyPostal><cmin:asCurrent>' . $this->escapeXml($data['phyPostal']) . '</cmin:asCurrent></cmin:phyPostal>
               <cmin:phoneDetails>' . $phoneDetailsXml . '
               </cmin:phoneDetails>
               <cmin:emailAddress><cmin:asCurrent>' . $this->escapeXml($data['email']) . '</cmin:asCurrent></cmin:emailAddress>
            </cmin:correspondence>

            <cmin:miscellaneous>
               <cmin:employerList>' . $employerListXml . '
               </cmin:employerList>
               <cmin:maritalStatus><cmin:asCurrent>' . $this->escapeXml($data['maritalStatus']) . '</cmin:asCurrent></cmin:maritalStatus>
               <cmin:condMarriage><cmin:asCurrent>' . $this->escapeXml($data['condMarriage']) . '</cmin:asCurrent></cmin:condMarriage>
               <cmin:spouseTIN><cmin:asCurrent>' . $this->escapeXml($data['spouseTIN']) . '</cmin:asCurrent></cmin:spouseTIN>
               <cmin:spouseName><cmin:asCurrent>' . $this->escapeXml($data['spouseName']) . '</cmin:asCurrent></cmin:spouseName>
               <cmin:spouseMaiden><cmin:asCurrent>' . $this->escapeXml($data['spouseMaiden']) . '</cmin:asCurrent></cmin:spouseMaiden>
               <cmin:spousePerID><cmin:asCurrent>' . $this->escapeXml($data['spousePerID']) . '</cmin:asCurrent></cmin:spousePerID>
            </cmin:miscellaneous>

            <cmin:mobile>
               <cmin:mobileDetails>' . $mobileDetailsXml . '
               </cmin:mobileDetails>
            </cmin:mobile>

            <cmin:bank>
               <cmin:bankDetails>' . $bankDetailsXml . '
               </cmin:bankDetails>
            </cmin:bank>

         </cmin:input>
      </cmin:CMINDEREG>
   </soapenv:Body>
</soapenv:Envelope>';

        return $soapXml;
    }

   protected function escapeXml($value): string
{
    // Convert null to empty string
    $value = $value ?? '';
    
    // Ensure it's a string
    $value = (string)$value;
    
    // Escape XML special characters
    return htmlspecialchars($value, ENT_XML1, 'UTF-8');
}

protected function extractTINFromSOAP(string $soapResponse): array
{
    try {
        // Clean the XML response
        $soapResponse = trim($soapResponse);
        
        Log::info('Parsing SOAP response', [
            'response_length' => strlen($soapResponse),
            'response_sample' => substr($soapResponse, 0, 300)
        ]);
        
        $tin = null;
        $errorMessage = null;
        
        // Simple regex to extract TIN
        if (preg_match('/<TIN>(.*?)<\/TIN>/', $soapResponse, $matches)) {
            $tin = trim($matches[1]);
            Log::info('Found TIN via regex', ['tin' => $tin]);
        }
        
        // Simple regex to extract message (only if no TIN found)
        if (!$tin && preg_match('/<message>(.*?)<\/message>/', $soapResponse, $matches)) {
            $errorMessage = trim($matches[1]);
            Log::info('Found message via regex', ['message' => $errorMessage]);
        }
        
        // If regex didn't work, try a more specific regex for the full path
        if (!$tin && !$errorMessage) {
            if (preg_match('/<confirmation>\s*<message>(.*?)<\/message>\s*<\/confirmation>/', $soapResponse, $matches)) {
                $errorMessage = trim($matches[1]);
                Log::info('Found message via detailed regex', ['message' => $errorMessage]);
            }
        }

        Log::info('SOAP parsing result', [
            'tin_found' => !empty($tin),
            'tin' => $tin,
            'error_message_found' => !empty($errorMessage),
            'error_message' => $errorMessage
        ]);

        return [
            'tin' => $tin,
            'error_message' => $errorMessage
        ];

    } catch (\Exception $e) {
        Log::error('Error extracting TIN from SOAP response', [
            'error' => $e->getMessage(),
            'response_sample' => substr($soapResponse, 0, 500)
        ]);
        return ['tin' => null, 'error_message' => 'Error parsing SOAP response: ' . $e->getMessage()];
    }
}
    protected function mockSoapCall(TinRegistration $registration): array
    {
        // Simulate API delay
        sleep(2);
        
        $mockTin = $registration->tin ?? 'MOCK' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        
        Log::info('Mock SOAP call executed', [
            'tin' => $registration->tin,
            'document_locator' => $registration->document_locator,
            'mock_tin' => $mockTin,
        ]);

        return [
            'success' => true,
            'tin' => $mockTin,
            'reference_id' => $mockTin,
            'message' => 'Mock: Registration submitted successfully to external system',
            'mock' => true,
        ];
    }
}