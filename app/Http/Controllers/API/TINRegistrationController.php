<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TINApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;



class TINRegistrationController extends Controller
{
    public function register(Request $request)
    {
        try {
            // Step 1: Validate input
             // ✅ Step 1: Validate request
        $validator = Validator::make($request->all(), [
            'regType'       => 'required|string',
            'effectiveDate' => 'required|date',
            'title'         => 'nullable|string|max:10',
            'surname'       => 'required|string|max:255',
            'forname'       => 'required|string|max:255',
            'proofID'       => 'required|string|max:50',
            'dateOfBirth'   => 'required|date',
            'passportNum'   => 'nullable|string|max:50',
            'passportExpiryDate' => 'nullable|date',
            'countryOfIssue'     => 'nullable|string|max:5',
            'countryOfBirth'     => 'nullable|string|max:5',
            'countryOfRes'       => 'nullable|string|max:5',
            'countryOfCit'       => 'nullable|string|max:5',

            'postCountry'   => 'nullable|string|max:5',
            'postType'      => 'nullable|string|max:10',
            'postNum'       => 'nullable|string|max:50',
            'postPostal'    => 'nullable|string|max:20',
            'postAddress1'  => 'nullable|string|max:255',
            'postCity'      => 'nullable|string|max:100',
            'postCounty'    => 'nullable|string|max:100',

            'phyCountry'    => 'nullable|string|max:5',
            'phyAddress1'   => 'nullable|string|max:255',
            'phyCity'       => 'nullable|string|max:100',
            'phyCounty'     => 'nullable|string|max:100',
            'phyPostal'     => 'nullable|string|max:20',

            'phoneType'     => 'nullable|string|max:20',
            'phoneCode'     => 'nullable|string|max:10',
            'phoneNumber'   => 'required|string|max:20',
            'email'         => 'required|email',

            'maritalStatus' => 'nullable|string|max:20',
            'condMarriage'  => 'nullable|string|max:50',
            'spouseTIN'     => 'nullable|string|max:50',
            'spouseName'    => 'nullable|string|max:255',
            'spouseMaiden'  => 'nullable|string|max:255',
            'spousePerID'   => 'nullable|string|max:50',

            'mobileMoney'        => 'nullable|string|max:20',
            'mobileMoneyNumber'  => 'nullable|string|max:20',
            'printedName'        => 'nullable|string|max:255',

            // ✅ File uploads
            'lesothoId'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'passport'    => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'otherId'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'foreignId'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',

            'employers' => 'nullable|array',
            'employers.*.name' => 'nullable|string',
            'employers.*.file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'   => 'Validation failed',
                'details' => $validator->errors(),
            ], 422);
        }

         // ✅ Step 2: Save files
        $uploads = [];
        foreach ([
            'lesothoId', 'passport', 'otherId',
            'foreignId', 'antenuptial_file'
        ] as $field) {
            if ($request->hasFile($field)) {
                $uploads[$field] = $request->file($field)->store('tin_uploads', 'public');
            }
        }

         $employersData = [];

         if ($request->has('employers')) {
             foreach ($request->input('employers') as $i => $emp) {
                 $filePath = null;

                 if ($request->hasFile("employers.$i.file")) {
                     $filePath = $request->file("employers.$i.file")
                                         ->store('tin_uploads/employers', 'public');
                 }

                 $employersData[] = [
                     'name' => $emp['name'] ?? null,
                     'file' => 'https://lenovo.rsl.org.ls/rsl_app/public/storage/'.$filePath,
                 ];
             }
         }


        // ✅ Step 3: Generate TIN (format: TIN-YYYY-XXXXXX)
        $tin = 'TIN-' . date('Y') . '-' . strtoupper(Str::random(6));

        // ✅ Step 4: Save to DB
        $registration = TINApplication::create([
            'regType'        => 'NEW',
            'effectiveDate'  => $request->effectiveDate,
            'title'           => $request->title,
            'surname'         => $request->surname,
            'forname'         => $request->forname,
            'proofID'        => $request->proofID,
            'dateOfBirth'   => $request->dateOfBirth,
            'passportNum'    => $request->passportNum,
            'passportExpiryDate' => $request->passportExpiryDate,
            'countryOfIssue'=> $request->countryOfIssue,
            'countryOfBirth'=> $request->countryOfBirth,
            'countryOfRes'  => $request->countryOfRes,
            'countryOfCit'  => $request->countryOfCit,

            'postCountry'   => $request->postCountry,
            'postType'      => $request->postType,
            'postNum'       => $request->postNum,
            'postPostal'    => $request->postPostal,
            'postAddress1'  => $request->postAddress1,
            'postCity'      => $request->postCity,
            'postCounty'    => $request->postCounty,

            'phyCountry'    => $request->phyCountry,
            'phyAddress1'   => $request->phyAddress1,
            'phyCity'       => $request->phyCity,
            'phyCounty'     => $request->phyCounty,
            'phyPostal'     => $request->phyPostal,

            'phoneType'     => $request->phoneType,
            'phoneCode'     => $request->phoneCode,
            'phoneNumber'   => $request->phoneNumber,
            'email'          => $request->email,

            'maritalStatus' => $request->maritalStatus,
            'condMarriage'  => $request->condMarriage,
            'spouseTIN'     => $request->spouseTIN,
            'spouseName'    => $request->spouseName,
            'spouseMaiden'  => $request->spouseMaiden,
            'spousePerID'  => $request->spousePerID,

            'mobileMoney'        => $request->mobileMoney,
            'mobileMoneyNumber' => $request->mobileMoneyNumber,
            'printedName'        => $request->printedName,

            'files' => json_encode($uploads),
            'employers' => json_encode($employersData),
        ]);

        // ✅ Step 5: Return success
        return response()->json([
            'success' => true,
            'message' => 'TIN registered successfully',
            
        ], 201);

            // Step 2: ID Validation from external API
           /* $idValidation = Http::timeout(10)->get('https://external.gov.api/validate/' . $validated['proofID']);
            if ($idValidation->failed() || !$idValidation->json('valid')) {
                return response()->json(['error' => 'ID validation failed.'], 422);
            }*/



            // Step 3: Store locally
   /*         $registration = TINApplication::create($validated);




            // Step 4: SOAP Request
            $soapXml = $this->buildSoapRequest($registration->toArray());

			try {
			    $soapResponse = Http::withHeaders([
			        'Content-Type' => 'text/xml; charset=utf-8',
			        'SOAPAction' => 'http://ouaf.oracle.com/spl/XAIXapp/xaiserver/CMINDEREG',
			    ])
			    ->withBasicAuth('USER22', 'password22')
			    ->withBody($soapXml, 'text/xml')
			    ->post('http://uatpsrmap02.lra.org.ls:6500/ouaf/XAIApp/xaiserver/CMINDEREG');

			    if ($soapResponse->failed()) {
			        Log::error("SOAP Failed", ['status' => $soapResponse->status(), 'body' => $soapResponse->body()]);
			    }

			} catch (\Exception $ex) {
			    Log::error("SOAP Exception", ['msg' => $ex->getMessage()]);
			}


			//Log::info('SOAP XML', ['request' => $soapXml]);
            Log::info('SOAP Raw Response', ['response' => $soapResponse->body()]);



            // Step 5: Parse TIN from SOAP XML
            $tin = $this->extractTINFromSOAP($soapResponse->body());

            if (!$tin) {
                return response()->json(['error' => 'TIN not returned from SOAP.'], 500);
            }

            // Step 6: Update record
            $registration->update(['generated_tin' => $tin]);

            return response()->json([
                'message' => 'TIN Registration successful.',
                'registration_id' => $registration->id,
                'tin' => $tin,
            ]);*/

        } catch (Exception $e) {
            Log::error("TIN Registration Error", ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Unexpected error occurred.'], 500);
        }
    }

  /*  private function buildSoapRequest($d)
{
    $today = now()->format('Y-m-d');

    return <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cmin="http://oracle.com/CMINDEREG.xsd">
   <soapenv:Header/>
   <soapenv:Body>
      <cmin:CMINDEREG dateTimeTagFormat="xsd:strict">
         <cmin:input>
            <cmin:documentLocator>DOC-{$d['proofID']}</cmin:documentLocator>
            <cmin:receiveDate>{$today}</cmin:receiveDate>
            <cmin:rsnForReg>
               <cmin:legacyTIN><cmin:asCurrent>{$d['legacyTIN']}</cmin:asCurrent></cmin:legacyTIN>
               <cmin:etpmTIN><cmin:asCurrent>{$d['etpmTIN']}</cmin:asCurrent></cmin:etpmTIN>
               <cmin:regType><cmin:asCurrent>{$d['regType']}</cmin:asCurrent></cmin:regType>
               <cmin:effectiveDate><cmin:asCurrent>{$d['effectiveDate']}</cmin:asCurrent></cmin:effectiveDate>
            </cmin:rsnForReg>

            <cmin:mainSection>
               <cmin:title><cmin:asCurrent>{$d['title']}</cmin:asCurrent></cmin:title>
               <cmin:surname><cmin:asCurrent>{$d['surname']}</cmin:asCurrent></cmin:surname>
               <cmin:forname><cmin:asCurrent>{$d['forname']}</cmin:asCurrent></cmin:forname>
               <cmin:name><cmin:asCurrent>{$d['name']}</cmin:asCurrent></cmin:name>
               <cmin:maidenName><cmin:asCurrent>{$d['maidenName']}</cmin:asCurrent></cmin:maidenName>
            </cmin:mainSection>

            <cmin:idAndResidency>
               <cmin:proofID><cmin:asCurrent>{$d['proofID']}</cmin:asCurrent></cmin:proofID>
               <cmin:dateOfBirth><cmin:asCurrent>{$d['dateOfBirth']}</cmin:asCurrent></cmin:dateOfBirth>
               <cmin:passportNum><cmin:asCurrent>{$d['passportNum']}</cmin:asCurrent></cmin:passportNum>
               <cmin:passportExpiryDate><cmin:asCurrent>{$d['passportExpiryDate']}</cmin:asCurrent></cmin:passportExpiryDate>
               <cmin:countryOfIssue><cmin:asCurrent>{$d['countryOfIssue']}</cmin:asCurrent></cmin:countryOfIssue>
               <cmin:otherID><cmin:asCurrent>{$d['otherID']}</cmin:asCurrent></cmin:otherID>
               <cmin:otherIDNumber><cmin:asCurrent>{$d['otherIDNumber']}</cmin:asCurrent></cmin:otherIDNumber>
               <cmin:driversExpiryDate><cmin:asCurrent>{$d['driversExpiryDate']}</cmin:asCurrent></cmin:driversExpiryDate>
               <cmin:otherCountryOfIssue><cmin:asCurrent>{$d['otherCountryOfIssue']}</cmin:asCurrent></cmin:otherCountryOfIssue>
               <cmin:countryOfBirth><cmin:asCurrent>{$d['countryOfBirth']}</cmin:asCurrent></cmin:countryOfBirth>
               <cmin:countryOfRes><cmin:asCurrent>{$d['countryOfRes']}</cmin:asCurrent></cmin:countryOfRes>
               <cmin:countryOfCit><cmin:asCurrent>{$d['countryOfCit']}</cmin:asCurrent></cmin:countryOfCit>
            </cmin:idAndResidency>

            <cmin:correspondence>
               <cmin:postCountry><cmin:asCurrent>{$d['postCountry']}</cmin:asCurrent></cmin:postCountry>
               <cmin:postType><cmin:asCurrent>{$d['postType']}</cmin:asCurrent></cmin:postType>
               <cmin:postNum><cmin:asCurrent>{$d['postNum']}</cmin:asCurrent></cmin:postNum>
               <cmin:postPostal><cmin:asCurrent>{$d['postPostal']}</cmin:asCurrent></cmin:postPostal>
               <cmin:postAddress1><cmin:asCurrent>{$d['postAddress1']}</cmin:asCurrent></cmin:postAddress1>
               <cmin:postCity><cmin:asCurrent>{$d['postCity']}</cmin:asCurrent></cmin:postCity>
               <cmin:postCounty><cmin:asCurrent>{$d['postCounty']}</cmin:asCurrent></cmin:postCounty>
               <cmin:phyCountry><cmin:asCurrent>{$d['phyCountry']}</cmin:asCurrent></cmin:phyCountry>
               <cmin:phyAddress1><cmin:asCurrent>{$d['phyAddress1']}</cmin:asCurrent></cmin:phyAddress1>
               <cmin:phyCity><cmin:asCurrent>{$d['phyCity']}</cmin:asCurrent></cmin:phyCity>
               <cmin:phyCounty><cmin:asCurrent>{$d['phyCounty']}</cmin:asCurrent></cmin:phyCounty>
               <cmin:phyPostal><cmin:asCurrent>{$d['phyPostal']}</cmin:asCurrent></cmin:phyPostal>
               <cmin:phoneDetails>
                  <cmin:phoneDetailsList>
                     <cmin:phoneType><cmin:asCurrent>{$d['phoneType']}</cmin:asCurrent></cmin:phoneType>
                     <cmin:phoneCode><cmin:asCurrent>{$d['phoneCode']}</cmin:asCurrent></cmin:phoneCode>
                     <cmin:phoneNumber><cmin:asCurrent>{$d['phoneNumber']}</cmin:asCurrent></cmin:phoneNumber>
                  </cmin:phoneDetailsList>
               </cmin:phoneDetails>
               <cmin:emailAddress><cmin:asCurrent>{$d['email']}</cmin:asCurrent></cmin:emailAddress>
            </cmin:correspondence>

            <cmin:miscellaneous>
               <cmin:employerList>
                  <cmin:employerListList>
                     <cmin:employer><cmin:asCurrent>none</cmin:asCurrent></cmin:employer>
                  </cmin:employerListList>
               </cmin:employerList>
               <cmin:maritalStatus><cmin:asCurrent>{$d['maritalStatus']}</cmin:asCurrent></cmin:maritalStatus>
               <cmin:condMarriage><cmin:asCurrent>{$d['condMarriage']}</cmin:asCurrent></cmin:condMarriage>
               <cmin:spouseTIN><cmin:asCurrent>{$d['spouseTIN']}</cmin:asCurrent></cmin:spouseTIN>
               <cmin:spouseName><cmin:asCurrent>{$d['spouseName']}</cmin:asCurrent></cmin:spouseName>
               <cmin:spouseMaiden><cmin:asCurrent>{$d['spouseMaiden']}</cmin:asCurrent></cmin:spouseMaiden>
               <cmin:spousePerID><cmin:asCurrent>{$d['spousePerID']}</cmin:asCurrent></cmin:spousePerID>
            </cmin:miscellaneous>



            <cmin:mobile>
               <cmin:mobileDetails>
                  <cmin:mobileDetailsList>
                     <cmin:mobileMoney><cmin:asCurrent>{$d['mobileMoney']}</cmin:asCurrent></cmin:mobileMoney>
                     <cmin:mobileMoneyNumber><cmin:asCurrent>{$d['mobileMoneyNumber']}</cmin:asCurrent></cmin:mobileMoneyNumber>
                     <cmin:accountAutoPayId><cmin:asCurrent>{$d['accountAutoPayId']}</cmin:asCurrent></cmin:accountAutoPayId>
                  </cmin:mobileDetailsList>
               </cmin:mobileDetails>
            </cmin:mobile>

         </cmin:input>
      </cmin:CMINDEREG>
   </soapenv:Body>
</soapenv:Envelope>
XML;
}


    private function extractTINFromSOAP($xml)
    {
        try {
            $xmlObj = simplexml_load_string($xml);
            $xmlObj->registerXPathNamespace('ns', 'http://oracle.com/CMINDEREG.xsd');
            $result = $xmlObj->xpath('//ns:TIN');
            return $result[0] ?? null;
        } catch (Exception $e) {
            Log::error('Failed to extract TIN: ' . $e->getMessage());
            return null;
        }
    }*/
}
