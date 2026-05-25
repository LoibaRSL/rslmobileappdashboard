<?php
// app/Services/Business/ExternalSoapService.php

namespace App\Services\Business;

use App\Models\BusinessRegistration;
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

    public function sendBusinessRegistration(BusinessRegistration $registration): array
{
    if ($this->mockMode) {
        return $this->mockSoapCall($registration);
    }

    try {
        // Load any relationships if they exist
        $registration->load([
            'files',
            'histories',
        ]);

        // Build SOAP XML request
        $soapXml = $this->buildSoapRequest($registration);

        Log::info('Business Registration SOAP Request XML', ['xml' => $soapXml]);

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
        Log::info('Business Registration SOAP Raw Response', [
            'registration_id' => $registration->id,
            'reference_number' => $registration->reference_number,
            'status' => $soapResponse->status(),
            'body' => $soapResponse->body(),
        ]);

        if ($soapResponse->failed()) {
            Log::error("Business Registration SOAP Request Failed", [
                'status' => $soapResponse->status(),
                'body' => $soapResponse->body(),
                'registration_id' => $registration->id,
                'reference_number' => $registration->reference_number,
            ]);

            throw new \Exception("SOAP request failed with status: {$soapResponse->status()}");
        }

        // Extract ALL data from SOAP response including message
        $responseData = $this->extractResponseData($soapResponse->body());
        
        // Check if this is a success or error response
        $isSuccess = $this->isSuccessfulResponse($soapResponse->body());
        
        if ($isSuccess && !empty($responseData['tin'])) {
            // SUCCESS: TIN was assigned
            return [
                'success' => true,
                'tin' => $responseData['tin'],
                'registration_form_id' => $responseData['registration_form_id'],
                'resperson_id' => $responseData['resperson_id'],
                'reference_id' => $registration->reference_number,
                'message' => $responseData['message'] ?? 'Business registration submitted successfully',
                'raw_response' => $soapResponse->body(),
            ];
        } else {
            // ERROR: No TIN or error message present
            $errorMessage = $responseData['message'] ?? 'Unknown error from external system';
            
            return [
                'success' => false,
                'tin' => null,
                'registration_form_id' => null,
                'resperson_id' => null,
                'reference_id' => $registration->reference_number,
                'message' => $errorMessage,
                'raw_response' => $soapResponse->body(),
            ];
        }

    } catch (\Exception $e) {
        Log::error("Business Registration SOAP Service Error", [
            'error' => $e->getMessage(),
            'registration_id' => $registration->id,
            'reference_number' => $registration->reference_number,
        ]);

        throw new \Exception("Business registration SOAP service error: {$e->getMessage()}");
    }
}
    protected function buildSoapRequest(BusinessRegistration $registration): string
    {
        // Extract data from JSON columns
        $structuredPostalAddress = $registration->structured_postal_address ?? [];
        if (is_string($structuredPostalAddress)) {
            $structuredPostalAddress = json_decode($structuredPostalAddress, true) ?? [];
        }
        
        $structuredPhysicalAddress = $registration->structured_physical_address ?? [];
        if (is_string($structuredPhysicalAddress)) {
            $structuredPhysicalAddress = json_decode($structuredPhysicalAddress, true) ?? [];
        }
        
        $structuredPhones = $registration->structured_phones ?? [];
        if (is_string($structuredPhones)) {
            $structuredPhones = json_decode($structuredPhones, true) ?? [];
        }
        
        // TRADE DETAILS
        $tradeDetails = $registration->trade_details ?? [];
        if (is_string($tradeDetails)) {
            $tradeDetails = json_decode($tradeDetails, true) ?? [];
        }
        
        $tradeDetails = $this->normaliseList($tradeDetails);
        
        // PRINCIPAL DETAILS
        $principalDetails = $registration->principal_details ?? [];
        if (is_string($principalDetails)) {
            $principalDetails = json_decode($principalDetails, true) ?? [];
        }
        
        // DIRECTORS/PARTNERS
        $directorsPartners = $registration->directors_partners ?? [];
        if (is_string($directorsPartners)) {
            $directorsPartners = json_decode($directorsPartners, true) ?? [];
        }
        
        // ACCOUNTANT DETAILS
        $accountantDetails = $registration->accountant_details ?? [];
        if (is_string($accountantDetails)) {
            $accountantDetails = json_decode($accountantDetails, true) ?? [];
        }
        
        // NOMINATED OFFICER DETAILS
        $nominatedOfficerDetails = $registration->nominated_officer_details ?? [];
        if (is_string($nominatedOfficerDetails)) {
            $nominatedOfficerDetails = json_decode($nominatedOfficerDetails, true) ?? [];
        }
        
        // PHONE DETAILS
        $phoneDetails = $registration->phone_details ?? [];
        if (is_string($phoneDetails)) {
            $phoneDetails = json_decode($phoneDetails, true) ?? [];
        }
        
        // PERSONAL IDENTIFICATION (for sole traders)
        $personalIdentification = $registration->personal_identification ?? [];
        if (is_string($personalIdentification)) {
            $personalIdentification = json_decode($personalIdentification, true) ?? [];
        }
        
        // SOLE TRADER DETAILS
        $soleTraderDetails = $registration->sole_trader_details ?? [];
        if (is_string($soleTraderDetails)) {
            $soleTraderDetails = json_decode($soleTraderDetails, true) ?? [];
        }

        // Extract name parts
        $nameStructure = $registration->name_structure ?? [];
        if (is_string($nameStructure)) {
            $nameStructure = json_decode($nameStructure, true) ?? [];
        }

        $surname = '';
        $forename = '';
        $maidenName = '';

        // Try to extract from name_structure
        if (!empty($nameStructure)) {
            $surname = $nameStructure['surname'] ?? 
                       $nameStructure['lastName'] ?? 
                       $nameStructure['last_name'] ?? '';
            
            $forename = $nameStructure['forename'] ?? 
                        $nameStructure['firstName'] ?? 
                        $nameStructure['first_name'] ?? 
                        $nameStructure['givenName'] ?? 
                        $nameStructure['given_name'] ?? '';
            
            $maidenName = $nameStructure['maidenName'] ?? 
                          $nameStructure['maiden_name'] ?? '';
        }

        // If still empty, check other sources
        if (empty($surname) || empty($forename)) {
            // Check sole_trader_details
            if (!empty($soleTraderDetails)) {
                if (empty($surname)) {
                    $surname = $soleTraderDetails['surname'] ?? '';
                }
                if (empty($forename)) {
                    $forename = $soleTraderDetails['forename'] ?? '';
                }
                if (empty($maidenName)) {
                    $maidenName = $soleTraderDetails['maidenName'] ?? $soleTraderDetails['maiden_name'] ?? '';
                }
            }
            
            // Check personal_identification
            if ((empty($surname) || empty($forename)) && !empty($personalIdentification)) {
                if (empty($surname)) {
                    $surname = $personalIdentification['surname'] ?? '';
                }
                if (empty($forename)) {
                    $forename = $personalIdentification['forename'] ?? '';
                }
                if (empty($maidenName)) {
                    $maidenName = $personalIdentification['maidenName'] ?? $personalIdentification['maiden_name'] ?? '';
                }
            }
        }

        // Build sections
        $phoneDetailsXml = $this->buildPhoneDetailsSection($phoneDetails, $structuredPhones);
        $principalDetailsXml = $this->buildPrincipalDetailsSection($principalDetails);
        $directorGroupXml = $this->buildDirectorSection($directorsPartners);
        $bankMobileMoney = $registration->bank_mobile_money ?? [];
        $bankDetails = $bankMobileMoney['bank_details'] ?? $bankMobileMoney['bankDetails'] ?? [];
        $mobileMoneyDetails = $bankMobileMoney['mobile_money_details']
            ?? $bankMobileMoney['mobileMoneyDetails']
            ?? $bankMobileMoney['mobile_money']
            ?? $bankMobileMoney['mobileMoney']
            ?? $registration->mobile_money_details
            ?? [];

        $bankDetailsXml = $this->buildBankDetailsSection($this->normaliseList($bankDetails));
        $mobileDetailsXml = $this->buildMobileMoneyDetailsSection($this->normaliseList($mobileMoneyDetails));
        $employerListXml = $this->buildEmployerListSection($soleTraderDetails);
        $fbtDetailsXml = $this->buildFbtDetailsSection([]);
        $whtTypeDetailsXml = $this->buildWhtTypeDetailsSection([]);
        
        // Build contact address section
        $contactAddressXml = $this->buildContactAddressSection($structuredPostalAddress, $structuredPhysicalAddress);
        
        // Build accountant section
        $accountantSectionXml = $this->buildAccountantSection($accountantDetails);
        
        // Build officer section (for non-sole traders)
        $officerSectionXml = '';
        if (!$registration->is_sole_trader) {
            $officerSectionXml = $this->buildOfficerSection($nominatedOfficerDetails);
        }
        
        // Build sole trader sections
        $soleIdResXml = '';
        $soleCorrespondenceXml = '';
        $soleMiscXml = '';
        if ($registration->is_sole_trader) {
            $soleIdResXml = $this->buildSoleIdResSection($personalIdentification);
           $soleCorrespondenceXml = $this->buildSoleCorrespondenceSection($structuredPostalAddress, $structuredPhysicalAddress, $structuredPhones);
            $soleMiscXml = $this->buildSoleMiscSection($soleTraderDetails);
        }

        // Build the complete SOAP XML
        $soapXml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cmb="http://oracle.com/CMBUSEREG.xsd">
   <soapenv:Header/>
   <soapenv:Body>
      <cmb:CMBUSEREG dateTimeTagFormat="xsd:strict">
         <cmb:input>
            <cmb:documentLocator>' . $this->escapeXml($registration->document_locator) . '</cmb:documentLocator>
            <cmb:receiveDate>' . $registration->created_at->format('Y-m-d') . '</cmb:receiveDate>
            <cmb:rsnForReg>
               <cmb:legacyTIN>
                  <cmb:asCurrent>' . $this->escapeXml($registration->old_tin ?? '') . '</cmb:asCurrent>
               </cmb:legacyTIN>
               <cmb:etpmTIN>
                  <cmb:asCurrent>' . $this->escapeXml($registration->new_tin ?? '') . '</cmb:asCurrent>
               </cmb:etpmTIN>
               <cmb:regType>
                  <cmb:asCurrent>' . $this->escapeXml($registration->registration_type) . '</cmb:asCurrent>
               </cmb:regType>
               <cmb:effectiveDate>
                  <cmb:asCurrent>' . $registration->receive_date->format('Y-m-d') . '</cmb:asCurrent>
               </cmb:effectiveDate>
            </cmb:rsnForReg>
            <cmb:detailsSection>
               <cmb:businessType>
                  <cmb:asCurrent>' . $this->escapeXml($registration->business_type) . '</cmb:asCurrent>
               </cmb:businessType>
               <cmb:otherBusinessType>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:otherBusinessType>
               <cmb:name>
                  <cmb:asCurrent>' . $this->escapeXml($registration->legal_name) . '</cmb:asCurrent>
               </cmb:name>
               <cmb:companyRegNumber>
                  <cmb:asCurrent>' . $this->escapeXml($registration->registration_number ?? '') . '</cmb:asCurrent>
               </cmb:companyRegNumber>
               <cmb:title>
                  <cmb:asCurrent>' . $this->escapeXml($registration->title ?? '') . '</cmb:asCurrent>
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
               <cmb:tradeNameDetails>' . $this->buildTradeNameDetailsSection($tradeDetails, $registration->legal_name, $registration->created_at->format('Y-m-d')) . '
               </cmb:tradeNameDetails>
            </cmb:tradenameSection>' .
            $principalDetailsXml . '
            <cmb:contactSection>' . $contactAddressXml .'<cmb:phoneDetails>'. $phoneDetailsXml . '</cmb:phoneDetails>
               <cmb:emailAddress>
                  <cmb:asCurrent>' . $this->escapeXml($registration->email) . '</cmb:asCurrent>
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
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:vatEffectiveDate>
               <cmb:vatReason>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:vatReason>
               <cmb:vatNewOrAcquired>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:vatNewOrAcquired>
               <cmb:vatNumber>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:vatNumber>
            </cmb:vatSection>
            <cmb:vatPreviousSection>
               <cmb:vatPreviousName>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:vatPreviousName>
               <cmb:vatPreviousTIN>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:vatPreviousTIN>
               <cmb:vatPerID>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:vatPerID>
               <cmb:vatPreviousAddress>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:vatPreviousAddress>
            </cmb:vatPreviousSection>
            <cmb:payeSection>
               <cmb:payeEffectiveDate>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:payeEffectiveDate>
               <cmb:employeeNumber>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:employeeNumber>
               <cmb:minSalary>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:minSalary>
               <cmb:maxSalary>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:maxSalary>
            </cmb:payeSection>
            <cmb:fbtSection>
               <cmb:fbtEffectiveDate>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:fbtEffectiveDate>
               <cmb:fbtDetails>
               </cmb:fbtDetails>
            </cmb:fbtSection>
            <cmb:whtSection>
               <cmb:whtEffectiveDate></cmb:whtEffectiveDate>
               <cmb:nonResSvcProvider>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:nonResSvcProvider>
               <cmb:nonResServProdDesc>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:nonResServProdDesc>
               <cmb:resContractors>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:resContractors>
               <cmb:resContractorsDesc>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:resContractorsDesc>
               <cmb:whtTypeDetails>
               </cmb:whtTypeDetails>
            </cmb:whtSection>
            <cmb:antlSection>
               <cmb:antlEffectiveDate>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:antlEffectiveDate>
               <cmb:antlNumber>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:antlNumber>
            </cmb:antlSection>
            <cmb:pLevySection>
               <cmb:pLevyEffectiveDate>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:pLevyEffectiveDate>
               <cmb:pLevyNumber>
                  <cmb:asCurrent></cmb:asCurrent>
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
                     <cmb:affalName><cmb:asCurrent>' . $this->escapeXml($principal['tradeName'] ?? '') . '</cmb:asCurrent></cmb:affalName>
                     <cmb:affalVATNumber><cmb:asCurrent>' . $this->escapeXml($principal['vatNumber'] ?? '') . '</cmb:asCurrent></cmb:affalVATNumber>
                     <cmb:affalCommission><cmb:asCurrent>' . $this->escapeXml($principal['commission'] ?? '') . '</cmb:asCurrent></cmb:affalCommission>
                     <cmb:affalcommencementDate><cmb:asCurrent>' . $this->escapeXml($principal['commencementDate'] ?? '') . '</cmb:asCurrent></cmb:affalcommencementDate>
                     <cmb:affalContractEndDate><cmb:asCurrent>' . $this->escapeXml($principal['contractEndDate'] ?? '') . '</cmb:asCurrent></cmb:affalContractEndDate>
                  </cmb:principalDetailsList>';
            }
        } else {
            $principalDetailsXml .= '
<cmb:principalDetailsList>
                     <cmb:affalName><cmb:asCurrent>' . $this->escapeXml($principal['tradeName'] ?? '') . '</cmb:asCurrent></cmb:affalName>
                     <cmb:affalVATNumber><cmb:asCurrent>' . $this->escapeXml($principal['vatNumber'] ?? '') . '</cmb:asCurrent></cmb:affalVATNumber>
                     <cmb:affalCommission><cmb:asCurrent>' . $this->escapeXml($principal['commission'] ?? '') . '</cmb:asCurrent></cmb:affalCommission>
                     <cmb:affalcommencementDate><cmb:asCurrent>' . $this->escapeXml($principal['commencementDate'] ?? '') . '</cmb:asCurrent></cmb:affalcommencementDate>
                     <cmb:affalContractEndDate><cmb:asCurrent>' . $this->escapeXml($principal['contractEndDate'] ?? '') . '</cmb:asCurrent></cmb:affalContractEndDate>
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
        $postType = $postalAddress['postalType'] ?? '';
        $postNum = $postalAddress['postalNumber'] ?? '';
        $postAddress1 = $postalAddress['formatted_address1'] ?? '';
        $postAddress2 = $postalAddress['formatted_address2'] ?? '';
        $postAddress3 = $postalAddress['formatted_address3'] ?? '';
        $postAddress4 = $postalAddress['formatted_address4'] ?? '';
        $postCity = $postalAddress['city'] ?? '';
        $postCounty = $postalAddress['district'] ?? '';
        $postPostal = $postalAddress['postalCode'] ?? '';

        $phyCountry = $physicalAddress['country'] ?? 'LS';
        $phyAddress1 = $physicalAddress['streetName'] ?? '';
        $phyAddress2 = $physicalAddress['nearestPlace'] ?? '';
        $phyAddress3 = $physicalAddress['village'] ?? '';
        $phyAddress4 = $physicalAddress['town'] ?? '';
        $phyCity = $physicalAddress['town'] ?? '';
        $phyCounty = $physicalAddress['district'] ?? '';
        $phyPostal = $physicalAddress['postalCode'] ?? '';

        return '
               <cmb:postCountry>
                  <cmb:asCurrent>' . $this->escapeXml($postCountry) . '</cmb:asCurrent>
               </cmb:postCountry>
               <cmb:postType>
                  <cmb:asCurrent>' . $this->escapeXml($postType) . '</cmb:asCurrent>
               </cmb:postType>
               <cmb:postNum>
                  <cmb:asCurrent>' . $this->escapeXml($postNum) . '</cmb:asCurrent>
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
                  <cmb:asCurrent>' . $this->escapeXml($postAddress3) . '</cmb:asCurrent>
               </cmb:postAddress3>
               <cmb:postAddress4>
                  <cmb:asCurrent>' . $this->escapeXml($postAddress4) . '</cmb:asCurrent>
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
                  <cmb:asCurrent>' . $this->escapeXml($phyAddress3) . '</cmb:asCurrent>
               </cmb:phyAddress3>
               <cmb:phyAddress4>
                  <cmb:asCurrent>' . $this->escapeXml($phyAddress4) . '</cmb:asCurrent>
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

    protected function buildAccountantSection(array $accountantDetails): string
    {
        if (empty($accountantDetails)) {
            return '';
        }

        $postCountry = $accountantDetails['country'] ?? 'LS';
        $postAddress1 = $accountantDetails['postal_address'] ?? '';
        $postCity = $accountantDetails['town'] ?? '';
        $postCounty = $accountantDetails['district'] ?? '';
        $postPostal = $accountantDetails['postal_code'] ?? '';

        $phyCountry = $accountantDetails['country'] ?? 'LS';
        $phyAddress1 = $accountantDetails['physical_address'] ?? $accountantDetails['chief_street_name'] ?? '';
        $phyCity = $accountantDetails['village'] ?? $accountantDetails['town'] ?? '';
        $phyCounty = $accountantDetails['district'] ?? '';
        $phyPostal = $accountantDetails['postal_code'] ?? '';

        return '
            <cmb:accountantSection>
               <cmb:name>
                  <cmb:asCurrent>' . $this->escapeXml($accountantDetails['name'] ?? '') . '</cmb:asCurrent>
               </cmb:name>
               <cmb:TIN>
                  <cmb:asCurrent>' . $this->escapeXml($accountantDetails['tin'] ?? '') . '</cmb:asCurrent>
               </cmb:TIN>
               <cmb:PerID>
                  <cmb:asCurrent>' . $this->escapeXml($accountantDetails['per_id'] ?? '') . '</cmb:asCurrent>
               </cmb:PerID>
               <cmb:postCountry>
                  <cmb:asCurrent>' . $this->escapeXml($postCountry) . '</cmb:asCurrent>
               </cmb:postCountry>
               <cmb:postType>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postType>
               <cmb:postNum>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postNum>
               <cmb:postPostal>
                  <cmb:asCurrent>' . $this->escapeXml($postPostal) . '</cmb:asCurrent>
               </cmb:postPostal>
               <cmb:postAddress1>
                  <cmb:asCurrent>' . $this->escapeXml($postAddress1) . '</cmb:asCurrent>
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
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyAddress2>
               <cmb:phyAddress3>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyAddress3>
               <cmb:phyAddress4>
                  <cmb:asCurrent></cmb:asCurrent>
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
               <cmb:phoneDetails>
                  <cmb:phoneDetailsList>
                     <cmb:phoneType>
                        <cmb:asCurrent>TEL</cmb:asCurrent>
                     </cmb:phoneType>
                     <cmb:phoneCode>
                        <cmb:asCurrent>266</cmb:asCurrent>
                     </cmb:phoneCode>
                     <cmb:phoneNumber>
                        <cmb:asCurrent>' . $this->escapeXml($accountantDetails['cell_phone'] ?? '') . '</cmb:asCurrent>
                     </cmb:phoneNumber>
                  </cmb:phoneDetailsList>
               </cmb:phoneDetails>
               <cmb:emailAddress>
                  <cmb:asCurrent>' . $this->escapeXml($accountantDetails['email'] ?? '') . '</cmb:asCurrent>
               </cmb:emailAddress>
            </cmb:accountantSection>';
    }

    protected function buildOfficerSection(array $officerDetails): string
    {
        if (empty($officerDetails)) {
            return '';
        }

        $postCountry = $officerDetails['country'] ?? 'LS';
        $postAddress1 = $officerDetails['postal_address'] ?? '';
        $postCity = $officerDetails['town'] ?? '';
        $postCounty = $officerDetails['district'] ?? '';
        $postPostal = $officerDetails['postal_code'] ?? '';

        $phyCountry = $officerDetails['country'] ?? 'LS';
        $phyAddress1 = $officerDetails['physical_address'] ?? $officerDetails['chief_street_name'] ?? '';
        $phyCity = $officerDetails['village'] ?? $officerDetails['town'] ?? '';
        $phyCounty = $officerDetails['district'] ?? '';
        $phyPostal = $officerDetails['postal_code'] ?? '';

        return '
            <cmb:officerSection>
               <cmb:name>
                  <cmb:asCurrent>' . $this->escapeXml($officerDetails['name'] ?? '') . '</cmb:asCurrent>
               </cmb:name>
               <cmb:TIN>
                  <cmb:asCurrent>' . $this->escapeXml($officerDetails['tin'] ?? '') . '</cmb:asCurrent>
               </cmb:TIN>
               <cmb:PerID>
                  <cmb:asCurrent>' . $this->escapeXml($officerDetails['per_id'] ?? '') . '</cmb:asCurrent>
               </cmb:PerID>
               <cmb:postCountry>
                  <cmb:asCurrent>' . $this->escapeXml($postCountry) . '</cmb:asCurrent>
               </cmb:postCountry>
               <cmb:postType>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postType>
               <cmb:postNum>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:postNum>
               <cmb:postPostal>
                  <cmb:asCurrent>' . $this->escapeXml($postPostal) . '</cmb:asCurrent>
               </cmb:postPostal>
               <cmb:postAddress1>
                  <cmb:asCurrent>' . $this->escapeXml($postAddress1) . '</cmb:asCurrent>
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
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyAddress2>
               <cmb:phyAddress3>
                  <cmb:asCurrent></cmb:asCurrent>
               </cmb:phyAddress3>
               <cmb:phyAddress4>
                  <cmb:asCurrent></cmb:asCurrent>
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
               <cmb:phoneDetails>
                  <cmb:phoneDetailsList>
                     <cmb:phoneType>
                        <cmb:asCurrent>TEL</cmb:asCurrent>
                     </cmb:phoneType>
                     <cmb:phoneCode>
                        <cmb:asCurrent>266</cmb:asCurrent>
                     </cmb:phoneCode>
                     <cmb:phoneNumber>
                        <cmb:asCurrent>' . $this->escapeXml($officerDetails['cell_phone'] ?? '') . '</cmb:asCurrent>
                     </cmb:phoneNumber>
                  </cmb:phoneDetailsList>
               </cmb:phoneDetails>
               <cmb:emailAddress>
                  <cmb:asCurrent>' . $this->escapeXml($officerDetails['email'] ?? '') . '</cmb:asCurrent>
               </cmb:emailAddress>
            </cmb:officerSection>';
    }

    protected function buildSoleIdResSection(array $personalIdentification): string
    {
        if (empty($personalIdentification)) {
            return '';
        }

        $dateOfBirth = '';
        if (!empty($personalIdentification['dateOfBirth'])) {
            $dateOfBirth = date('Y-m-d', strtotime($personalIdentification['dateOfBirth']));
        }

        $lesothoIdExpiry = '';
        if (!empty($personalIdentification['lesothoIdExpiry'])) {
            $lesothoIdExpiry = date('Y-m-d', strtotime($personalIdentification['lesothoIdExpiry']));
        }

        $otherIdExpiryDate = '';
        if (!empty($personalIdentification['otherIdExpiryDate'])) {
            $otherIdExpiryDate = date('Y-m-d', strtotime($personalIdentification['otherIdExpiryDate']));
        }

        return '
            <cmb:soleIdResSection>
               <cmb:dateOfBirth>
                  <cmb:asCurrent>' . $this->escapeXml($dateOfBirth) . '</cmb:asCurrent>
               </cmb:dateOfBirth>
               <cmb:passportNum>
                  <cmb:asCurrent>' . $this->escapeXml($personalIdentification['lesothoIdNumber'] ?? '') . '</cmb:asCurrent>
               </cmb:passportNum>
               <cmb:passportExpiryDate>
                  <cmb:asCurrent>' . $this->escapeXml($lesothoIdExpiry) . '</cmb:asCurrent>
               </cmb:passportExpiryDate>
               <cmb:countryOfIssue>
                  <cmb:asCurrent>' . $this->escapeXml($personalIdentification['countryOfIssue'] ?? '') . '</cmb:asCurrent>
               </cmb:countryOfIssue>
               <cmb:otherID>
                  <cmb:asCurrent>' . $this->escapeXml($personalIdentification['otherIdType'] ?? '') . '</cmb:asCurrent>
               </cmb:otherID>
               <cmb:otherIDNumber>
                  <cmb:asCurrent>' . $this->escapeXml($personalIdentification['otherIdNumber'] ?? '') . '</cmb:asCurrent>
               </cmb:otherIDNumber>
               <cmb:otherIDExpiryDate>
                  <cmb:asCurrent>' . $this->escapeXml($otherIdExpiryDate) . '</cmb:asCurrent>
               </cmb:otherIDExpiryDate>
               <cmb:otherCountryOfIssue>
                  <cmb:asCurrent>' . $this->escapeXml($personalIdentification['otherCountryOfIssue'] ?? '') . '</cmb:asCurrent>
               </cmb:otherCountryOfIssue>
               <cmb:countryOfBirth>
                  <cmb:asCurrent>' . $this->escapeXml($personalIdentification['countryOfBirth'] ?? '') . '</cmb:asCurrent>
               </cmb:countryOfBirth>
               <cmb:countryOfRes>
                  <cmb:asCurrent>' . $this->escapeXml($personalIdentification['countryOfResidence'] ?? '') . '</cmb:asCurrent>
               </cmb:countryOfRes>
               <cmb:countryOfCit>
                  <cmb:asCurrent>' . $this->escapeXml($personalIdentification['countryOfCitizenship'] ?? '') . '</cmb:asCurrent>
               </cmb:countryOfCit>
            </cmb:soleIdResSection>';
    }

    protected function buildSoleCorrespondenceSection(array $postalAddress, array $physicalAddress, array $phones): string
    {
        $postCountry = $postalAddress['country'] ?? 'LS';
        $postAddress1 = $postalAddress['addressLine1'] ?? '';
        $postAddress2 = $postalAddress['addressLine2'] ?? '';
        $postCity = $postalAddress['city'] ?? '';
        $postCounty = $postalAddress['district'] ?? '';
        $postPostal = $postalAddress['postalCode'] ?? '';

        $phyCountry = $physicalAddress['country'] ?? 'LS';
        $phyAddress1 = $physicalAddress['village'] ?? '';
        $phyAddress2 = $physicalAddress['streetName'] ?? '';
        $phyCity = $physicalAddress['city'] ?? '';
        $phyCounty = $physicalAddress['district'] ?? '';
        $phyPostal = $physicalAddress['postalCode'] ?? '';

        // Build phone details for sole trader
        $phoneXml = '';
        if (!empty($phones)) {
            foreach ($phones as $phone) {
                if (!empty($phone['phoneNumber'])) {
                    $phoneXml .= '
                     <cmb:phoneDetailsList>
                        <cmb:phoneType>
                           <cmb:asCurrent>' . $this->escapeXml($phone['phoneType'] ?? 'CEL1') . '</cmb:asCurrent>
                        </cmb:phoneType>
                        <cmb:phoneCode>
                           <cmb:asCurrent>' . $this->escapeXml($phone['phoneCode'] ?? '266') . '</cmb:asCurrent>
                        </cmb:phoneCode>
                        <cmb:phoneNumber>
                           <cmb:asCurrent>' . $this->escapeXml($phone['phoneNumber']) . '</cmb:asCurrent>
                        </cmb:phoneNumber>
                     </cmb:phoneDetailsList>';
                }
            }
        }

        return 'ok'; 
    }

    protected function buildSoleMiscSection(array $soleTraderDetails): string
    {
        $employerListXml = $this->buildEmployerListSection($soleTraderDetails);

        return '
            <cmb:soleMiscSection>
               ' . $employerListXml . '
               <cmb:maritalStatus>
                  <cmb:asCurrent>' . $this->escapeXml($soleTraderDetails['maritalStatus'] ?? '') . '</cmb:asCurrent>
               </cmb:maritalStatus>
               <cmb:condMarriage>
                  <cmb:asCurrent>' . $this->escapeXml($soleTraderDetails['marriageCondition'] ?? '') . '</cmb:asCurrent>
               </cmb:condMarriage>
               <cmb:spouseTIN>
                  <cmb:asCurrent>' . $this->escapeXml($soleTraderDetails['spouseTIN'] ?? '') . '</cmb:asCurrent>
               </cmb:spouseTIN>
               <cmb:spouseName>
                  <cmb:asCurrent>' . $this->escapeXml($soleTraderDetails['spouseName'] ?? '') . '</cmb:asCurrent>
               </cmb:spouseName>
               <cmb:spouseMaiden>
                  <cmb:asCurrent>' . $this->escapeXml($soleTraderDetails['spouseMaidenName'] ?? '') . '</cmb:asCurrent>
               </cmb:spouseMaiden>
               <cmb:spousePerID>
                  <cmb:asCurrent>' . $this->escapeXml($soleTraderDetails['spousePerID'] ?? '') . '</cmb:asCurrent>
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

    // The following helper methods remain the same as before:

    protected function buildPhoneDetailsSection(array $phoneDetails, array $structuredPhones): string
    {
        $phoneDetailsXml = '';

        if (empty($phoneDetails) && !empty($structuredPhones)) {
            $phoneDetails = $structuredPhones;
        }

        if (!empty($phoneDetails)) {
            foreach ($phoneDetails as $phone) {
                $phoneNumber = $phone['phoneNumber'] ?? $phone['phone_number'] ?? '';
                if (is_array($phoneNumber)) {
                    $phoneNumber = $phoneNumber['text'] ?? '';
                }
                
                if (!empty($phoneNumber)) {
                    $phoneDetailsXml .= '
                  <cmb:phoneDetailsList>
                     <cmb:phoneType><cmb:asCurrent>' . $this->escapeXml($phone['phoneType'] ?? $phone['phone_type'] ?? 'CEL1') . '</cmb:asCurrent></cmb:phoneType>
                     <cmb:phoneCode><cmb:asCurrent>' . $this->escapeXml($phone['phoneCode'] ?? $phone['phone_code'] ?? '266') . '</cmb:asCurrent></cmb:phoneCode>
                     <cmb:phoneNumber><cmb:asCurrent>' . $this->escapeXml($phoneNumber) . '</cmb:asCurrent></cmb:phoneNumber>
                  </cmb:phoneDetailsList>';
                }
            }
        }
       /* elseif (!empty($structuredPhones)) {
            foreach ($structuredPhones as $phone) {
                if (!empty($phone['phoneNumber'])) {
                    $phoneDetailsXml .= '
                  <cmb:phoneDetailsList>
                     <cmb:phoneType><cmb:asCurrent>' . $this->escapeXml($phone['phoneType'] ?? 'CEL1') . '</cmb:asCurrent></cmb:phoneType>
                     <cmb:phoneCode><cmb:asCurrent>' . $this->escapeXml($phone['phoneCode'] ?? '266') . '</cmb:asCurrent></cmb:phoneCode>
                     <cmb:phoneNumber><cmb:asCurrent>' . $this->escapeXml($phone['phoneNumber']) . '</cmb:asCurrent></cmb:phoneNumber>
                  </cmb:phoneDetailsList>';
                }
            }
        } */
        
      

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

    protected function buildTradeNameDetailsSection(array $tradeDetails, ?string $fallbackName, string $fallbackDate): string
    {
        $tradeDetails = $this->normaliseList($tradeDetails);

        if (empty($tradeDetails)) {
            $tradeDetails = [[
                'tradeName' => $fallbackName,
                'commencementDate' => $fallbackDate,
            ]];
        }

        $xml = '';
        foreach ($tradeDetails as $trade) {
            $xml .= '
                  <cmb:tradeNameDetailsList>
                     <cmb:tradeName>
                        <cmb:asCurrent>' . $this->escapeXml($trade['tradeName'] ?? $trade['trade_name'] ?? $fallbackName) . '</cmb:asCurrent>
                     </cmb:tradeName>
                     <cmb:natureOfBusiness>
                        <cmb:asCurrent>' . $this->escapeXml($trade['natureOfBusinessCode'] ?? $trade['nature_of_business_code'] ?? '') . '</cmb:asCurrent>
                     </cmb:natureOfBusiness>
                     <cmb:seatCapacity>
                        <cmb:asCurrent>' . $this->escapeXml($trade['seatCapacity'] ?? $trade['seat_capacity'] ?? '') . '</cmb:asCurrent>
                     </cmb:seatCapacity>
                     <cmb:vehiclereg>
                        <cmb:asCurrent>' . $this->escapeXml($trade['vehicleReg'] ?? $trade['vehicle_reg'] ?? '') . '</cmb:asCurrent>
                     </cmb:vehiclereg>
                     <cmb:businessActivity>
                        <cmb:asCurrent>' . $this->escapeXml($trade['businessActivity'] ?? $trade['business_activity'] ?? '') . '</cmb:asCurrent>
                     </cmb:businessActivity>
                     <cmb:sbtTurnOver>
                        <cmb:asCurrent>' . $this->escapeXml($trade['sbtTurnOver'] ?? $trade['sbt_turnover'] ?? '') . '</cmb:asCurrent>
                     </cmb:sbtTurnOver>
                     <cmb:commencementDate>
                        <cmb:asCurrent>' . $this->escapeXml($trade['commencementDate'] ?? $trade['commencement_date'] ?? $fallbackDate) . '</cmb:asCurrent>
                     </cmb:commencementDate>
                     <cmb:traderNumber>
                        <cmb:asCurrent>' . $this->escapeXml($trade['licenseNumber'] ?? $trade['trader_number'] ?? $trade['traderNumber'] ?? '') . '</cmb:asCurrent>
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

    protected function extractResponseData(string $soapResponse): array
{
    try {
        $xml = simplexml_load_string($soapResponse);
        
        if ($xml === false) {
            Log::error('Failed to parse business registration SOAP response XML');
            return [
                'tin' => null, 
                'registration_form_id' => null, 
                'resperson_id' => null,
                'message' => 'Failed to parse XML response'
            ];
        }

        // Register namespaces
        $xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml->registerXPathNamespace('cmb', 'http://oracle.com/CMBUSEREG.xsd');

        // Extract data from response
        $tin = null;
        $registrationFormId = null;
        $personId = null;
        $message = null;

        // Extract message
        $messageResult = $xml->xpath('//cmb:message');
        if (!empty($messageResult)) {
            $message = (string)$messageResult[0];
        }

        // Extract TIN only if message is not an error
        if ($message && !str_contains($message, 'You have been successfully registered')) {
            // This is an error message, don't extract TIN
            Log::info('SOAP response contains error message', ['message' => $message]);
        } else {
            // Extract TIN
            $tinResult = $xml->xpath('//cmb:TIN');
            if (!empty($tinResult)) {
                $tin = (string)$tinResult[0];
            }

            // Extract registration form ID
            $formIdResult = $xml->xpath('//cmb:registrationFormId');
            if (!empty($formIdResult)) {
                $registrationFormId = (string)$formIdResult[0];
            }

            // Extract person ID
            $personIdResult = $xml->xpath('//cmb:personID');
            if (!empty($personIdResult)) {
                $personId = (string)$personIdResult[0];
            }
        }

        Log::info('Business registration response data extracted', [
            'tin' => $tin,
            'registration_form_id' => $registrationFormId,
            'resperson_id' => $personId,
            'message' => $message
        ]);

        return [
            'tin' => $tin,
            'registration_form_id' => $registrationFormId,
            'resperson_id' => $personId,
            'message' => $message
        ];

    } catch (\Exception $e) {
        Log::error('Error extracting data from business registration SOAP response', [
            'error' => $e->getMessage(),
            'response_sample' => substr($soapResponse, 0, 500)
        ]);
        return [
            'tin' => null, 
            'registration_form_id' => null, 
            'resperson_id' => null,
            'message' => 'Error parsing response: ' . $e->getMessage()
        ];
    }
}

/**
 * Check if SOAP response indicates success
 * Success messages contain "You have been successfully registered"
 */
protected function isSuccessfulResponse(string $soapResponse): bool
{
    try {
        $xml = simplexml_load_string($soapResponse);
        
        if ($xml === false) {
            return false;
        }

        // Register namespaces
        $xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml->registerXPathNamespace('cmb', 'http://oracle.com/CMBUSEREG.xsd');

        // Extract message
        $messageResult = $xml->xpath('//cmb:message');
        
        if (!empty($messageResult)) {
            $message = (string)$messageResult[0];
            
            // Check if this is a success message
            return str_contains($message, 'You have been successfully registered');
        }
        
        return false;
        
    } catch (\Exception $e) {
        Log::error('Failed to check if SOAP response is successful', [
            'error' => $e->getMessage(),
        ]);
        return false;
    }
}
    protected function mockSoapCall(BusinessRegistration $registration): array
    {
        // Simulate API delay
        sleep(2);
        
        $mockTin = $registration->new_tin ?? 'BUS' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $mockFormId = 'FORM-' . $registration->reference_number;
        $mockPersonId = 'PERS-' . $registration->reference_number;
        
        Log::info('Mock business registration SOAP call executed', [
            'reference_number' => $registration->reference_number,
            'mock_tin' => $mockTin,
            'mock_form_id' => $mockFormId,
            'mock_person_id' => $mockPersonId,
        ]);

        return [
            'success' => true,
            'tin' => $mockTin,
            'registration_form_id' => $mockFormId,
            'person_id' => $mockPersonId,
            'reference_id' => $registration->reference_number,
            'message' => 'Mock: Business registration submitted successfully to external system',
            'mock' => true,
        ];
    }
}
