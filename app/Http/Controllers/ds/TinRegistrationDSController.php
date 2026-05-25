<?php

namespace App\Http\Controllers\ds;

use App\Http\Controllers\Controller;
use App\Models\BusinessAmendment;
use App\Models\BusinessAmendmentFile;
use App\Models\BusinessRegistration;
use App\Models\BusinessRegistrationFile;
use App\Models\RegistrationFile;
use App\Models\RegistrationOperationEvent;
use App\Models\TinRegistration;
use App\Models\User;
use App\Services\Business\ExternalSoapService as BusinessSoapService;
use App\Services\Business\amend\ExternalSoapService as BusinessAmendmentSoapService;
use App\Services\AiReportService;
use App\Services\ExternalSoapService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TinRegistrationDSController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasPermission('registration.dashboard')) {
            return $this->forbidden('You do not have permission to access the registration dashboard');
        }

        $individualApproved = TinRegistration::where('status', 'APPROVED')->count();
        $individualRejected = TinRegistration::where('status', 'REJECTED')->count();
        $businessApproved = BusinessRegistration::where('status', 'approved')->count();
        $businessRejected = BusinessRegistration::where('status', 'rejected')->count();
        $businessAmendmentApproved = BusinessAmendment::where('status', 'approved')->count();
        $businessAmendmentRejected = BusinessAmendment::where('status', 'rejected')->count();
        $approved = $individualApproved + $businessApproved;
        $rejected = $individualRejected + $businessRejected;
        $approved += $businessAmendmentApproved;
        $rejected += $businessAmendmentRejected;
        $processed = $approved + $rejected;

        return response()->json([
            'success' => true,
            'stats' => [
                'unassigned' => $this->unassignedIndividualQuery()->count() + $this->unassignedBusinessQuery()->count() + $this->unassignedBusinessAmendmentQuery()->count(),
                'assigned_to_me' => TinRegistration::where('assigned_to', (string) $user->id)->whereIn('status', ['PENDING', 'UNDER_REVIEW'])->count()
                    + BusinessRegistration::where('assigned_to', (string) $user->id)->whereIn('status', ['submitted', 'under_review'])->count()
                    + BusinessAmendment::where('assigned_to', (string) $user->id)->whereIn('status', ['submitted', 'under_review'])->count(),
                'approved' => $approved,
                'rejected' => $rejected,
                'my_approved' => $approved,
                'my_rejected' => $rejected,
                'total_pending' => TinRegistration::whereIn('status', ['PENDING', 'UNDER_REVIEW'])->count()
                    + BusinessRegistration::whereIn('status', ['submitted', 'under_review'])->count()
                    + BusinessAmendment::whereIn('status', ['submitted', 'under_review'])->count(),
                'approval_rate' => $processed > 0 ? round(($approved / $processed) * 100, 1) : 0,
            ],
        ]);
    }

    public function getAllRegistrations(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to view registrations');
        }

        return $this->individualDataTableResponse($request, 'all');
    }

    public function getUnassigned(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.assign')) {
            return $this->forbidden('You do not have permission to assign registrations');
        }

        return $this->individualDataTableResponse($request, 'unassigned');
    }

    public function getMyAssignments(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to view registrations');
        }

        return $this->individualDataTableResponse($request, 'assigned_to_me');
    }

    public function getApproved(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to view registrations');
        }

        return $this->individualDataTableResponse($request, 'approved');
    }

    public function getRejected(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to view registrations');
        }

        return $this->individualDataTableResponse($request, 'rejected');
    }

    public function getIndividualAmendments(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to view amendments');
        }

        return $this->individualDataTableResponse($request, 'all', true);
    }

    public function getAllBusinessRegistrations(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to view registrations');
        }

        return $this->businessDataTableResponse($request, 'all');
    }

    public function getUnassignedBusiness(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.assign')) {
            return $this->forbidden('You do not have permission to assign registrations');
        }

        return $this->businessDataTableResponse($request, 'unassigned');
    }

    public function getMyBusinessAssignments(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to view registrations');
        }

        return $this->businessDataTableResponse($request, 'assigned_to_me');
    }

    public function getApprovedBusiness(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to view registrations');
        }

        return $this->businessDataTableResponse($request, 'approved');
    }

    public function getRejectedBusiness(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to view registrations');
        }

        return $this->businessDataTableResponse($request, 'rejected');
    }

    public function getBusinessAmendments(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to view amendments');
        }

        return $this->businessAmendmentDataTableResponse($request, 'all');
    }

    public function show($id): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to view registration details');
        }

        $registration = TinRegistration::with(['employers', 'files', 'bankingDetails', 'mobileMoneyDetails', 'phoneDetails'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'registration' => $this->formatIndividualRegistration($registration),
        ]);
    }

    public function showBusiness($id): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to view registration details');
        }

        $registration = BusinessRegistration::with(['files', 'histories'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'registration' => $this->formatBusinessRegistration($registration),
        ]);
    }

    public function showBusinessAmendment($id): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to view amendment details');
        }

        $amendment = BusinessAmendment::with(['registration', 'files', 'histories'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'registration' => $this->formatBusinessAmendment($amendment),
        ]);
    }

    public function assignToSelf($id): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.assign')) {
            return $this->forbidden('You do not have permission to assign registrations');
        }

        $registration = TinRegistration::findOrFail($id);
        $this->assignIndividualRegistration($registration, auth()->id());

        return response()->json(['success' => true, 'message' => 'Registration assigned to you successfully']);
    }

    public function assignBusinessToSelf($id): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.assign')) {
            return $this->forbidden('You do not have permission to assign registrations');
        }

        $registration = BusinessRegistration::findOrFail($id);
        $this->assignBusinessRegistration($registration, auth()->id());

        return response()->json(['success' => true, 'message' => 'Business registration assigned to you successfully']);
    }

    public function assignBusinessAmendmentToSelf($id): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.assign')) {
            return $this->forbidden('You do not have permission to assign amendments');
        }

        $amendment = BusinessAmendment::findOrFail($id);
        $this->assignBusinessAmendment($amendment, auth()->id());

        return response()->json(['success' => true, 'message' => 'Business amendment assigned to you successfully']);
    }

    public function assignToUser($id, Request $request): JsonResponse
    {
        $registration = TinRegistration::findOrFail($id);

        return $this->assignModelToUser($registration, $request, false);
    }

    public function assignBusinessToUser($id, Request $request): JsonResponse
    {
        $registration = BusinessRegistration::findOrFail($id);

        return $this->assignModelToUser($registration, $request, true);
    }

    public function assignBusinessAmendmentToUser($id, Request $request): JsonResponse
    {
        $amendment = BusinessAmendment::findOrFail($id);

        $user = auth()->user();

        if (!$user->hasPermission('registration.assign') && !$user->hasPermission('registration.reassign')) {
            return $this->forbidden('You do not have permission to assign amendments');
        }

        $request->validate(['user_id' => 'required|exists:users,id']);

        if (!empty($amendment->assigned_to) && !$user->hasPermission('registration.reassign')) {
            return $this->forbidden('You do not have permission to reassign amendments');
        }

        $assignee = User::findOrFail($request->user_id);
        if (!$assignee->isDigitalServices()) {
            return response()->json(['success' => false, 'message' => 'Amendments can only be assigned to Digital Services users'], 422);
        }

        $this->assignBusinessAmendment($amendment, $assignee->id);

        return response()->json(['success' => true, 'message' => 'Business amendment assigned successfully']);
    }

    public function approve($id, Request $request, ExternalSoapService $soapService, SmsService $smsService): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.approve')) {
            return $this->forbidden('You do not have permission to approve registrations');
        }

        $request->validate([
            'tin' => 'nullable|string|max:50',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $registration = TinRegistration::with(['phoneDetails', 'bankingDetails', 'mobileMoneyDetails', 'employers'])->findOrFail($id);
        $isAmendment = $this->isIndividualAmendment($registration);

        $soapResult = $soapService->sendTinRegistration($registration);
        if (!($soapResult['success'] ?? false)) {
            $this->logOperation($registration, 'soap', 'soap', 'failed', 'SOAP submission failed', $soapResult['message'] ?? $soapResult['error_message'] ?? 'Unknown error', $soapResult);
            Log::warning('Individual registration SOAP approval failed', [
                'registration_id' => $registration->id,
                'message' => $soapResult['message'] ?? null,
                'error' => $soapResult['error_message'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SOAP backend submission failed: ' . ($soapResult['message'] ?? $soapResult['error_message'] ?? 'Unknown error'),
            ], 422);
        }

        $approvedTin = $soapResult['tin'] ?: $registration->tin;
        if (!$isAmendment && empty($approvedTin)) {
            $this->logOperation($registration, 'soap', 'soap', 'failed', 'SOAP did not return TIN', 'The backend accepted the call but did not return a TIN.', $soapResult);

            return response()->json([
                'success' => false,
                'message' => 'SOAP backend did not return a TIN. Registration was not approved.',
            ], 422);
        }

        $this->logOperation($registration, 'soap', 'soap', 'success', 'SOAP submission succeeded', $soapResult['message'] ?? 'SOAP submitted successfully', $soapResult);
        $registration->update([
            'tin' => $approvedTin,
            'status' => 'APPROVED',
            'remarks' => $request->remarks ?: ($soapResult['message'] ?? 'Registration approved by Digital Services.'),
        ]);

        $smsResult = $this->sendIndividualApprovalSms($registration, $smsService, $isAmendment);
        $this->logOperation($registration, 'sms', 'sms', ($smsResult['success'] ?? false) ? 'success' : 'failed', 'Approval SMS', $smsResult['message'] ?? null, $smsResult);
        $this->logOperation($registration, 'action', 'workflow', 'success', $isAmendment ? 'Amendment approved' : 'Registration approved', $registration->remarks);
        $smsMessage = ($smsResult['success'] ?? false) ? ' SMS sent to client.' : ' SMS not sent: ' . ($smsResult['message'] ?? 'No phone number.');

        return response()->json([
            'success' => true,
            'message' => ($isAmendment ? 'Amendment approved successfully.' : 'Registration approved successfully.') . $smsMessage,
        ]);
    }

    public function approveBusiness($id, Request $request, BusinessSoapService $soapService, SmsService $smsService): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.approve')) {
            return $this->forbidden('You do not have permission to approve registrations');
        }

        $request->validate([
            'tin' => 'nullable|string|max:50',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $registration = BusinessRegistration::findOrFail($id);
        if ($request->filled('tin')) {
            $registration->new_tin = $request->tin;
            $registration->save();
        }

        try {
            $soapResult = $soapService->sendBusinessRegistration($registration);
        } catch (\Throwable $e) {
            Log::warning('Business registration SOAP approval failed', [
                'registration_id' => $registration->id,
                'reference_number' => $registration->reference_number,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SOAP backend submission failed: ' . $e->getMessage(),
            ], 422);
        }

        if (!($soapResult['success'] ?? false)) {
            $this->logOperation($registration, 'soap', 'soap', 'failed', 'SOAP submission failed', $soapResult['message'] ?? 'Unknown error', $soapResult);
            return response()->json([
                'success' => false,
                'message' => 'SOAP backend submission failed: ' . ($soapResult['message'] ?? 'Unknown error'),
            ], 422);
        }

        $approvedTin = $soapResult['tin'] ?: $registration->new_tin;
        $this->logOperation($registration, 'soap', 'soap', 'success', 'SOAP submission succeeded', $soapResult['message'] ?? 'SOAP submitted successfully', $soapResult);
        $registration->update([
            'new_tin' => $approvedTin,
            'person_id' => $soapResult['resperson_id'] ?? $soapResult['person_id'] ?? $registration->person_id,
            'status' => 'approved',
            'review_notes' => $request->remarks ?: ($soapResult['message'] ?? 'Business registration approved by Digital Services.'),
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ]);

        $smsResult = $this->sendBusinessApprovalSms($registration, $smsService);
        $this->logOperation($registration, 'sms', 'sms', ($smsResult['success'] ?? false) ? 'success' : 'failed', 'Approval SMS', $smsResult['message'] ?? null, $smsResult);
        $this->logOperation($registration, 'action', 'workflow', 'success', 'Business registration approved', $registration->review_notes);
        $smsMessage = ($smsResult['success'] ?? false) ? ' SMS sent to client.' : ' SMS not sent: ' . ($smsResult['message'] ?? 'No phone number.');

        return response()->json(['success' => true, 'message' => 'Business registration approved successfully.' . $smsMessage]);
    }

    public function approveBusinessAmendment($id, Request $request, BusinessAmendmentSoapService $soapService, SmsService $smsService): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.approve')) {
            return $this->forbidden('You do not have permission to approve amendments');
        }

        $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $amendment = BusinessAmendment::with('registration')->findOrFail($id);

        try {
            $soapResult = $soapService->sendBusinessAmendment($amendment);
        } catch (\Throwable $e) {
            Log::warning('Business amendment SOAP approval failed', [
                'amendment_id' => $amendment->id,
                'reference_number' => $amendment->reference_number,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SOAP backend submission failed: ' . $e->getMessage(),
            ], 422);
        }

        if (!($soapResult['success'] ?? false)) {
            $this->logOperation($amendment, 'soap', 'soap', 'failed', 'SOAP submission failed', $soapResult['message'] ?? 'Unknown error', $soapResult);
            return response()->json([
                'success' => false,
                'message' => 'SOAP backend submission failed: ' . ($soapResult['message'] ?? 'Unknown error'),
            ], 422);
        }

        $this->logOperation($amendment, 'soap', 'soap', 'success', 'SOAP submission succeeded', $soapResult['message'] ?? 'SOAP submitted successfully', $soapResult);
        $amendment->update([
            'status' => 'approved',
            'review_notes' => $request->remarks ?: ($soapResult['message'] ?? 'Business amendment approved by Digital Services.'),
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ]);

        $smsResult = $this->sendBusinessAmendmentApprovalSms($amendment, $smsService);
        $this->logOperation($amendment, 'sms', 'sms', ($smsResult['success'] ?? false) ? 'success' : 'failed', 'Approval SMS', $smsResult['message'] ?? null, $smsResult);
        $this->logOperation($amendment, 'action', 'workflow', 'success', 'Business amendment approved', $amendment->review_notes);
        $smsMessage = ($smsResult['success'] ?? false) ? ' SMS sent to client.' : ' SMS not sent: ' . ($smsResult['message'] ?? 'No phone number.');

        return response()->json(['success' => true, 'message' => 'Business amendment approved successfully.' . $smsMessage]);
    }

    public function reject($id, Request $request, SmsService $smsService): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.reject')) {
            return $this->forbidden('You do not have permission to reject registrations');
        }

        $request->validate(['remarks' => 'required|string|min:5|max:1000']);

        $registration = TinRegistration::with('phoneDetails')->findOrFail($id);
        $registration->update([
            'status' => 'REJECTED',
            'remarks' => 'Registration rejected. Reason: ' . $request->remarks,
        ]);

        $smsResult = $this->sendIndividualRejectionSms($registration, $smsService, $request->remarks);
        $this->logOperation($registration, 'sms', 'sms', ($smsResult['success'] ?? false) ? 'success' : 'failed', 'Rejection SMS', $smsResult['message'] ?? null, $smsResult);
        $this->logOperation($registration, 'action', 'workflow', 'success', 'Registration rejected', $request->remarks);
        $smsMessage = ($smsResult['success'] ?? false) ? ' SMS sent to client.' : ' SMS not sent: ' . ($smsResult['message'] ?? 'No phone number.');

        return response()->json(['success' => true, 'message' => 'Registration rejected successfully.' . $smsMessage]);
    }

    public function rejectBusiness($id, Request $request, SmsService $smsService): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.reject')) {
            return $this->forbidden('You do not have permission to reject registrations');
        }

        $request->validate(['remarks' => 'required|string|min:5|max:1000']);

        $registration = BusinessRegistration::findOrFail($id);
        $registration->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
            'rejection_reason' => $request->remarks,
        ]);

        $smsResult = $this->sendBusinessRejectionSms($registration, $smsService, $request->remarks);
        $this->logOperation($registration, 'sms', 'sms', ($smsResult['success'] ?? false) ? 'success' : 'failed', 'Rejection SMS', $smsResult['message'] ?? null, $smsResult);
        $this->logOperation($registration, 'action', 'workflow', 'success', 'Business registration rejected', $request->remarks);
        $smsMessage = ($smsResult['success'] ?? false) ? ' SMS sent to client.' : ' SMS not sent: ' . ($smsResult['message'] ?? 'No phone number.');

        return response()->json(['success' => true, 'message' => 'Business registration rejected successfully.' . $smsMessage]);
    }

    public function rejectBusinessAmendment($id, Request $request, SmsService $smsService): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.reject')) {
            return $this->forbidden('You do not have permission to reject amendments');
        }

        $request->validate(['remarks' => 'required|string|min:5|max:1000']);

        $amendment = BusinessAmendment::with('registration')->findOrFail($id);
        $amendment->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
            'rejection_reason' => $request->remarks,
        ]);

        $smsResult = $this->sendBusinessAmendmentRejectionSms($amendment, $smsService, $request->remarks);
        $this->logOperation($amendment, 'sms', 'sms', ($smsResult['success'] ?? false) ? 'success' : 'failed', 'Rejection SMS', $smsResult['message'] ?? null, $smsResult);
        $this->logOperation($amendment, 'action', 'workflow', 'success', 'Business amendment rejected', $request->remarks);
        $smsMessage = ($smsResult['success'] ?? false) ? ' SMS sent to client.' : ' SMS not sent: ' . ($smsResult['message'] ?? 'No phone number.');

        return response()->json(['success' => true, 'message' => 'Business amendment rejected successfully.' . $smsMessage]);
    }

    public function getAssignmentHistory(): JsonResponse
    {
        return response()->json(['success' => true, 'history' => []]);
    }

    public function failedSoapQueue(): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to view failed SOAP submissions');
        }

        $events = RegistrationOperationEvent::where('event_type', 'soap')
            ->latest()
            ->get()
            ->groupBy(fn ($event) => $event->subject_type . ':' . $event->subject_id)
            ->map(fn ($events) => $events->first())
            ->filter(fn ($event) => $event->status === 'failed')
            ->values()
            ->map(fn ($event) => [
                'id' => $event->id,
                'subject_label' => $event->subject_label,
                'subject_type_label' => class_basename($event->subject_type),
                'message' => $event->message,
                'failed_at' => $event->created_at?->format('Y-m-d H:i:s'),
            ]);

        return response()->json(['success' => true, 'data' => $events]);
    }

    public function retryFailedSoap(
        RegistrationOperationEvent $event,
        ExternalSoapService $individualSoap,
        BusinessSoapService $businessSoap,
        BusinessAmendmentSoapService $businessAmendmentSoap,
        SmsService $smsService
    ): JsonResponse {
        if (!auth()->user()->hasPermission('registration.approve')) {
            return $this->forbidden('You do not have permission to retry SOAP submissions');
        }

        if ($event->event_type !== 'soap' || $event->status !== 'failed') {
            return response()->json(['success' => false, 'message' => 'This SOAP event is not retryable.'], 422);
        }

        $subject = $event->subject;
        if (!$subject) {
            return response()->json(['success' => false, 'message' => 'Original record could not be found.'], 404);
        }

        try {
            if ($subject instanceof TinRegistration) {
                $soapResult = $individualSoap->sendTinRegistration($subject);
                if (!($soapResult['success'] ?? false)) {
                    throw new \RuntimeException($soapResult['message'] ?? $soapResult['error_message'] ?? 'SOAP retry failed');
                }
                $subject->update(['tin' => $soapResult['tin'] ?: $subject->tin, 'status' => 'APPROVED', 'remarks' => $soapResult['message'] ?? 'Approved after SOAP retry']);
                $smsResult = $this->sendIndividualApprovalSms($subject, $smsService, $this->isIndividualAmendment($subject));
            } elseif ($subject instanceof BusinessRegistration) {
                $soapResult = $businessSoap->sendBusinessRegistration($subject);
                if (!($soapResult['success'] ?? false)) {
                    throw new \RuntimeException($soapResult['message'] ?? 'SOAP retry failed');
                }
                $subject->update([
                    'new_tin' => $soapResult['tin'] ?: $subject->new_tin,
                    'person_id' => $soapResult['resperson_id'] ?? $soapResult['person_id'] ?? $subject->person_id,
                    'status' => 'approved',
                    'review_notes' => $soapResult['message'] ?? 'Approved after SOAP retry',
                    'reviewed_at' => now(),
                    'reviewed_by' => auth()->id(),
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                ]);
                $smsResult = $this->sendBusinessApprovalSms($subject, $smsService);
            } elseif ($subject instanceof BusinessAmendment) {
                $soapResult = $businessAmendmentSoap->sendBusinessAmendment($subject);
                if (!($soapResult['success'] ?? false)) {
                    throw new \RuntimeException($soapResult['message'] ?? 'SOAP retry failed');
                }
                $subject->update([
                    'status' => 'approved',
                    'review_notes' => $soapResult['message'] ?? 'Approved after SOAP retry',
                    'reviewed_at' => now(),
                    'reviewed_by' => auth()->id(),
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                ]);
                $smsResult = $this->sendBusinessAmendmentApprovalSms($subject, $smsService);
            } else {
                return response()->json(['success' => false, 'message' => 'Unsupported record type.'], 422);
            }

            $event->update(['status' => 'retried']);
            $this->logOperation($subject, 'soap', 'soap', 'success', 'SOAP retry succeeded', $soapResult['message'] ?? 'SOAP retry succeeded', $soapResult);
            $this->logOperation($subject, 'sms', 'sms', ($smsResult['success'] ?? false) ? 'success' : 'failed', 'Retry approval SMS', $smsResult['message'] ?? null, $smsResult);

            return response()->json(['success' => true, 'message' => 'SOAP retry succeeded and the record was approved.']);
        } catch (\Throwable $e) {
            $this->logOperation($subject, 'soap', 'soap', 'failed', 'SOAP retry failed', $e->getMessage(), ['retry_of' => $event->id]);

            return response()->json(['success' => false, 'message' => 'SOAP retry failed: ' . $e->getMessage()], 422);
        }
    }

    public function slaDashboard(): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to view SLA data');
        }

        $rows = collect()
            ->merge(TinRegistration::whereIn('status', ['PENDING', 'UNDER_REVIEW'])->get()->map(fn ($item) => $this->slaRow($item, 'Individual', $item->ref, $this->getIndividualFullName($item), $item->status)))
            ->merge(BusinessRegistration::whereIn('status', ['submitted', 'under_review'])->get()->map(fn ($item) => $this->slaRow($item, 'Business', $item->reference_number, $item->display_name, $this->displayStatusFromBusiness($item->status))))
            ->merge(BusinessAmendment::with('registration')->whereIn('status', ['submitted', 'under_review'])->get()->map(fn ($item) => $this->slaRow($item, 'Business Amendment', $item->reference_number, $this->getBusinessAmendmentName($item), $this->displayStatusFromBusiness($item->status))))
            ->sortByDesc('age_days')
            ->values();

        return response()->json([
            'success' => true,
            'stats' => [
                'open' => $rows->count(),
                'overdue' => $rows->where('overdue', true)->count(),
                'due_soon' => $rows->where('due_soon', true)->count(),
                'average_age_days' => round($rows->avg('age_days') ?: 0, 1),
            ],
            'data' => $rows->take(100)->values(),
        ]);
    }

    public function timeline(string $type, int $id): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to view timeline data');
        }

        $subject = match ($type) {
            'individual' => TinRegistration::findOrFail($id),
            'business' => BusinessRegistration::findOrFail($id),
            'business_amendment' => BusinessAmendment::findOrFail($id),
            default => abort(404),
        };

        return response()->json(['success' => true, 'data' => $this->formatTimeline($subject)]);
    }

    public function attachment(string $kind, int $file)
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            abort(403, 'You do not have permission to view registration attachments');
        }

        $attachment = match ($kind) {
            'individual', 'individual_amendment' => RegistrationFile::findOrFail($file),
            'business' => BusinessRegistrationFile::findOrFail($file),
            'business_amendment' => BusinessAmendmentFile::findOrFail($file),
            default => abort(404),
        };

        $disk = $attachment->disk ?? 'public';
        $path = ltrim((string) $attachment->file_path, '/\\');
        $path = preg_replace('#^storage/#', '', $path);
        $path = preg_replace('#^public/#', '', $path);

        if (!Storage::disk($disk)->exists($path)) {
            abort(404, 'Attachment file not found');
        }

        $filename = $attachment->file_name
            ?? $attachment->original_filename
            ?? basename($path);

        return response()->file(Storage::disk($disk)->path($path), [
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
        ]);
    }

    public function aiCaseSummary(string $type, int $id, AiReportService $aiReportService): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return $this->forbidden('You do not have permission to use AI case summaries');
        }

        $subject = $this->resolveAiSubject($type, $id);
        $result = $aiReportService->generate(
            'Summarise this registration case for a Digital Services officer. Use no personal names, emails, phone numbers, TINs, or reference numbers. Return: key status, likely next action, risks/blockers, and a short checklist.',
            $this->aiCaseContext($subject)
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function aiExplainSoapFailure(RegistrationOperationEvent $event, AiReportService $aiReportService): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.approve')) {
            return $this->forbidden('You do not have permission to analyse SOAP failures');
        }

        if ($event->event_type !== 'soap' || $event->status !== 'failed') {
            return response()->json(['success' => false, 'message' => 'Only failed SOAP events can be analysed.'], 422);
        }

        $result = $aiReportService->generate(
            'Explain this failed SOAP submission for an operations user. Do not mention personal identifiers. Return likely cause, what to check, retry guidance, and escalation notes.',
            [
                'record_type' => class_basename($event->subject_type),
                'event_title' => $event->title,
                'failure_message' => $event->message,
                'metadata' => $this->safeSoapMetadata($event->metadata ?? []),
                'failed_at' => $event->created_at?->toDateTimeString(),
            ]
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function aiDraftRejection(Request $request, AiReportService $aiReportService): JsonResponse
    {
        if (!auth()->user()->hasPermission('registration.reject')) {
            return $this->forbidden('You do not have permission to draft rejection messages');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
            'type' => ['nullable', 'string', 'max:80'],
        ]);

        $result = $aiReportService->generate(
            'Rewrite the officer note into a clear, professional SMS-friendly rejection reason for a taxpayer. Do not add facts. Do not include personal identifiers. Keep it under 320 characters.',
            [
                'record_type' => $validated['type'] ?? 'registration',
                'officer_note' => $validated['reason'],
            ]
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function export(Request $request)
    {
        if (!auth()->user()->hasPermission('registration.view')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $exportType = $request->get('type');
        $registrations = match ($exportType) {
            'business' => $this->businessRows($request, 'all')->sortByDesc('created_timestamp'),
            'individual_amendment' => $this->individualRows($request, 'all', true)->sortByDesc('created_timestamp'),
            'business_amendment' => $this->businessAmendmentRows($request, 'all')->sortByDesc('created_timestamp'),
            default => $this->individualRows($request, 'all')->sortByDesc('created_timestamp'),
        };
        $filenamePrefix = match ($exportType) {
            'business' => 'ds_business_registrations_',
            'individual_amendment' => 'ds_individual_amendments_',
            'business_amendment' => 'ds_business_amendments_',
            default => 'ds_individual_registrations_',
        };
        $filename = $filenamePrefix . date('Y-m-d_His') . '.csv';

        return response()->stream(function () use ($registrations) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Type', 'ID', 'Reference', 'TIN', 'Name', 'Email', 'Assigned To', 'Status', 'Submitted Date']);

            foreach ($registrations as $registration) {
                fputcsv($file, [
                    $registration['registration_type_label'],
                    $registration['id'],
                    $registration['ref'],
                    $registration['tin'],
                    $registration['full_name'],
                    $registration['email'],
                    $registration['assigned_to'],
                    $registration['status'],
                    $registration['submitted_at'],
                ]);
            }

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    public function getDsUsers(): JsonResponse
    {
        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['digital_services', 'admin']);
        })->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'users' => $users->map(fn ($user) => ['id' => $user->id, 'name' => $user->name])->values(),
        ]);
    }

    public function getStats(): JsonResponse
    {
        return $this->dashboard();
    }

    private function individualDataTableResponse(Request $request, string $scope, bool $amendmentsOnly = false): JsonResponse
    {
        $rows = $this->individualRows($request, $scope, $amendmentsOnly)->sortByDesc('created_timestamp')->values();
        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 25);

        return response()->json([
            'success' => true,
            'data' => ['data' => $rows->slice($start, $length)->values()],
            'recordsTotal' => $this->totalIndividualRegistrationCount($scope, $amendmentsOnly),
            'recordsFiltered' => $rows->count(),
            'draw' => (int) $request->get('draw', 1),
        ]);
    }

    private function businessDataTableResponse(Request $request, string $scope): JsonResponse
    {
        $rows = $this->businessRows($request, $scope)->sortByDesc('created_timestamp')->values();
        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 25);

        return response()->json([
            'success' => true,
            'data' => ['data' => $rows->slice($start, $length)->values()],
            'recordsTotal' => $this->totalBusinessRegistrationCount($scope),
            'recordsFiltered' => $rows->count(),
            'draw' => (int) $request->get('draw', 1),
        ]);
    }

    private function businessAmendmentDataTableResponse(Request $request, string $scope): JsonResponse
    {
        $rows = $this->businessAmendmentRows($request, $scope)->sortByDesc('created_timestamp')->values();
        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 25);

        return response()->json([
            'success' => true,
            'data' => ['data' => $rows->slice($start, $length)->values()],
            'recordsTotal' => BusinessAmendment::count(),
            'recordsFiltered' => $rows->count(),
            'draw' => (int) $request->get('draw', 1),
        ]);
    }

    private function individualRows(Request $request, string $scope, bool $amendmentsOnly = false): Collection
    {
        $query = TinRegistration::query();

        $this->applyIndividualRegistrationTypeScope($query, $amendmentsOnly);
        $this->applyIndividualFilters($query, $request);
        $this->applyIndividualScope($query, $scope);

        return $query->get()->map(fn (TinRegistration $registration) => $this->formatIndividualRegistrationRow($registration));
    }

    private function applyIndividualRegistrationTypeScope($query, bool $amendmentsOnly): void
    {
        if ($amendmentsOnly) {
            $query->whereIn('registration_type', ['AMND', 'AMEND']);
            return;
        }

        $query->where(function ($q) {
            $q->whereNull('registration_type')
                ->orWhere('registration_type', '')
                ->orWhere('registration_type', 'NEW');
        });
    }

    private function businessRows(Request $request, string $scope): Collection
    {
        $query = BusinessRegistration::query();

        $this->applyBusinessFilters($query, $request);
        $this->applyBusinessScope($query, $scope);

        return $query->get()->map(fn (BusinessRegistration $registration) => $this->formatBusinessRegistrationRow($registration));
    }

    private function businessAmendmentRows(Request $request, string $scope): Collection
    {
        $query = BusinessAmendment::with('registration');

        $this->applyBusinessAmendmentFilters($query, $request);
        $this->applyBusinessAmendmentScope($query, $scope);

        return $query->get()->map(fn (BusinessAmendment $amendment) => $this->formatBusinessAmendmentRow($amendment));
    }

    private function applyIndividualFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ref', 'like', "%{$search}%")
                    ->orWhere('tin', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('forenames', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $this->applyDateFilters($query, $request);
    }

    private function applyBusinessFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('new_tin', 'like', "%{$search}%")
                    ->orWhere('old_tin', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $this->businessStatusFromDisplay($request->status));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $this->applyDateFilters($query, $request);
    }

    private function applyBusinessAmendmentFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('tin', 'like', "%{$search}%")
                    ->orWhere('amendment_tin', 'like', "%{$search}%")
                    ->orWhere('document_locator', 'like', "%{$search}%")
                    ->orWhereHas('registration', function ($registrationQuery) use ($search) {
                        $registrationQuery->where('legal_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $this->businessStatusFromDisplay($request->status));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $this->applyDateFilters($query, $request);
    }

    private function applyDateFilters($query, Request $request): void
    {
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
    }

    private function applyIndividualScope($query, string $scope): void
    {
        if ($scope === 'unassigned') {
            $query->where(function ($q) {
                $q->whereNull('assigned_to')->orWhere('assigned_to', '');
            })->where('status', 'PENDING');
        } elseif ($scope === 'assigned_to_me') {
            $query->where('assigned_to', (string) auth()->id());
        } elseif ($scope === 'approved') {
            $query->where('status', 'APPROVED');
        } elseif ($scope === 'rejected') {
            $query->where('status', 'REJECTED');
        }
    }

    private function applyBusinessScope($query, string $scope): void
    {
        if ($scope === 'unassigned') {
            $query->where(function ($q) {
                $q->whereNull('assigned_to')->orWhere('assigned_to', '');
            })->where('status', 'submitted');
        } elseif ($scope === 'assigned_to_me') {
            $query->where('assigned_to', (string) auth()->id());
        } elseif ($scope === 'approved') {
            $query->where('status', 'approved');
        } elseif ($scope === 'rejected') {
            $query->where('status', 'rejected');
        }
    }

    private function applyBusinessAmendmentScope($query, string $scope): void
    {
        if ($scope === 'unassigned') {
            $query->where(function ($q) {
                $q->whereNull('assigned_to')->orWhere('assigned_to', '');
            })->where('status', 'submitted');
        } elseif ($scope === 'assigned_to_me') {
            $query->where('assigned_to', (string) auth()->id());
        } elseif ($scope === 'approved') {
            $query->where('status', 'approved');
        } elseif ($scope === 'rejected') {
            $query->where('status', 'rejected');
        }
    }

    private function assignModelToUser($registration, Request $request, bool $business): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasPermission('registration.assign') && !$user->hasPermission('registration.reassign')) {
            return $this->forbidden('You do not have permission to assign registrations');
        }

        $request->validate(['user_id' => 'required|exists:users,id']);

        if (!empty($registration->assigned_to) && !$user->hasPermission('registration.reassign')) {
            return $this->forbidden('You do not have permission to reassign registrations');
        }

        $assignee = User::findOrFail($request->user_id);
        if (!$assignee->isDigitalServices()) {
            return response()->json(['success' => false, 'message' => 'Registrations can only be assigned to Digital Services users'], 422);
        }

        $business
            ? $this->assignBusinessRegistration($registration, $assignee->id)
            : $this->assignIndividualRegistration($registration, $assignee->id);

        return response()->json(['success' => true, 'message' => 'Registration assigned successfully']);
    }

    private function assignIndividualRegistration(TinRegistration $registration, int $userId): void
    {
        $registration->update([
            'assigned_to' => (string) $userId,
            'status' => $registration->status === 'PENDING' ? 'UNDER_REVIEW' : $registration->status,
        ]);
        $this->logOperation($registration, 'action', 'workflow', 'success', 'Assigned to user', 'Assigned to ' . (User::find($userId)?->name ?? 'user'));
    }

    private function assignBusinessRegistration(BusinessRegistration $registration, int $userId): void
    {
        $registration->update([
            'assigned_to' => (string) $userId,
            'assigned_to_user_id' => $userId,
            'assigned_at' => now(),
            'status' => $registration->status === 'submitted' ? 'under_review' : $registration->status,
            'reviewed_at' => now(),
            'reviewed_by' => $userId,
        ]);
        $this->logOperation($registration, 'action', 'workflow', 'success', 'Assigned to user', 'Assigned to ' . (User::find($userId)?->name ?? 'user'));
    }

    private function assignBusinessAmendment(BusinessAmendment $amendment, int $userId): void
    {
        $amendment->update([
            'assigned_to' => (string) $userId,
            'assigned_to_user_id' => $userId,
            'assigned_at' => now(),
            'status' => $amendment->status === 'submitted' ? 'under_review' : $amendment->status,
            'reviewed_at' => now(),
            'reviewed_by' => $userId,
        ]);
        $this->logOperation($amendment, 'action', 'workflow', 'success', 'Assigned to user', 'Assigned to ' . (User::find($userId)?->name ?? 'user'));
    }

    private function formatIndividualRegistrationRow(TinRegistration $registration): array
    {
        $isAmendment = in_array($registration->registration_type, ['AMND', 'AMEND'], true);

        return [
            'registration_kind' => 'individual',
            'registration_type_label' => $isAmendment ? 'Individual Amendment' : 'Individual',
            'registration_type' => $registration->registration_type,
            'id' => $registration->id,
            'ref' => $registration->ref,
            'tin' => $registration->tin ?? 'N/A',
            'full_name' => $this->getIndividualFullName($registration),
            'email' => $registration->email,
            'assigned_to' => $this->getAssignedToName($registration->assigned_to),
            'assigned_at' => $this->latestAssignmentTime($registration),
            'status' => $registration->status,
            'submitted_at' => $registration->created_at?->format('Y-m-d H:i:s'),
            'created_timestamp' => $registration->created_at?->timestamp ?? 0,
        ];
    }

    private function formatBusinessRegistrationRow(BusinessRegistration $registration): array
    {
        return [
            'registration_kind' => 'business',
            'registration_type_label' => 'Business',
            'id' => $registration->id,
            'ref' => $registration->reference_number,
            'tin' => $registration->new_tin ?? $registration->old_tin ?? 'N/A',
            'full_name' => $registration->display_name,
            'email' => $registration->email,
            'assigned_to' => $this->getAssignedToName($registration->assigned_to),
            'assigned_at' => $registration->assigned_at?->format('Y-m-d H:i:s') ?? 'N/A',
            'status' => $this->displayStatusFromBusiness($registration->status),
            'submitted_at' => $registration->created_at?->format('Y-m-d H:i:s'),
            'created_timestamp' => $registration->created_at?->timestamp ?? 0,
        ];
    }

    private function formatBusinessAmendmentRow(BusinessAmendment $amendment): array
    {
        return [
            'registration_kind' => 'business_amendment',
            'registration_type_label' => 'Business Amendment',
            'registration_type' => 'AMEND',
            'id' => $amendment->id,
            'ref' => $amendment->reference_number,
            'tin' => data_get($amendment->amendment_data, 'etpm_tin')
                ?? $amendment->amendment_tin
                ?? $amendment->original_tin
                ?? 'N/A',
            'full_name' => $this->getBusinessAmendmentName($amendment),
            'email' => $this->getBusinessAmendmentEmail($amendment),
            'assigned_to' => $this->getAssignedToName($amendment->assigned_to),
            'assigned_at' => $amendment->assigned_at?->format('Y-m-d H:i:s') ?? 'N/A',
            'status' => $this->displayStatusFromBusiness($amendment->status),
            'submitted_at' => $amendment->created_at?->format('Y-m-d H:i:s'),
            'created_timestamp' => $amendment->created_at?->timestamp ?? 0,
        ];
    }

    private function formatIndividualRegistration(TinRegistration $registration): array
    {
        $phoneDetails = $registration->phoneDetails
            ->map(fn ($phone) => $this->displayFields([
                'phone_type' => $phone->phone_type,
                'phone_code' => $phone->phone_code,
                'phone_number' => $phone->phone_number,
                'full_phone_number' => trim(($phone->phone_code ?? '') . ' ' . ($phone->phone_number ?? '')),
            ]))
            ->values()
            ->all();

        $bankingDetails = $registration->bankingDetails
            ->map(fn ($bank) => $this->displayFields($bank->only([
                'bank_code',
                'bank_country',
                'account_holder_name',
                'branch',
                'account_number',
                'account_type',
                'swift_code',
                'branch_code',
            ])))
            ->values()
            ->all();

        $mobileMoneyDetails = $registration->mobileMoneyDetails
            ->map(fn ($mobile) => $this->displayFields($mobile->only([
                'mobile_money_type',
                'mobile_money_number',
            ])))
            ->values()
            ->all();

        if ($mobileMoneyDetails === []) {
            $fallbackMobileMoney = $this->displayFields($registration->only([
                'mobile_money_type',
                'mobile_money_number',
            ]));

            if ($fallbackMobileMoney !== []) {
                $mobileMoneyDetails = [$fallbackMobileMoney];
            }
        }

        $employerDetails = $registration->employers
            ->map(fn ($employer) => $this->displayFields($employer->only([
                'employer_name',
                'file_path',
            ])))
            ->values()
            ->all();

        $fileKind = $this->isIndividualAmendment($registration) ? 'individual_amendment' : 'individual';
        $files = $registration->files
            ->map(function ($file) use ($fileKind) {
                $file->registration_kind = $fileKind;
                return $file;
            })
            ->values();

        return array_merge($this->formatIndividualRegistrationRow($registration), [
            'title' => $registration->title,
            'surname' => $registration->surname,
            'forenames' => $registration->forenames,
            'maiden_name' => $registration->maiden_name,
            'date_of_birth' => $registration->date_of_birth,
            'country_of_birth' => $this->displayValue('country_of_birth', $registration->country_of_birth),
            'country_of_citizenship' => $this->displayValue('country_of_citizenship', $registration->country_of_citizenship),
            'country_of_residence' => $this->displayValue('country_of_residence', $registration->country_of_residence),
            'phone_details' => $phoneDetails,
            'banking_details' => $bankingDetails,
            'mobile_money_details' => $mobileMoneyDetails,
            'employers' => $employerDetails,
            'files' => $files,
            'remarks' => $registration->remarks,
            'assignment_history' => [],
            'timeline' => $this->formatTimeline($registration),
            'sections' => [
                'Reference' => $this->displayFields($registration->only(['id', 'ref', 'document_locator', 'receive_date', 'effective_date', 'registration_type', 'legacy_tin', 'tin', 'status'])),
                'Personal Details' => $this->displayFields($registration->only(['title', 'surname', 'forenames', 'maiden_name', 'date_of_birth', 'marital_status', 'condition_of_marriage'])),
                'Identification And Residency' => $this->displayFields($registration->only(['lesotho_id_number', 'lesotho_id_expiry', 'country_of_issue', 'other_id_type', 'other_id_number', 'other_id_expiry', 'country_of_birth', 'country_of_citizenship', 'country_of_residence'])),
                'Postal Address' => $this->displayFields($registration->only(['post_country', 'post_type', 'post_number', 'post_code', 'post_address1', 'post_address2', 'post_address3', 'post_address4', 'post_city', 'post_district'])),
                'Physical Address' => $this->displayFields($registration->only(['physical_country', 'street_name', 'nearest_place', 'village', 'town', 'physical_district', 'phy_postal'])),
                'Contact Details' => $this->displayFields($registration->only(['phone_type', 'phone_code', 'phone_number', 'email', 'email_verified'])),
                'Phone Details' => $phoneDetails,
                'Employment Details' => $employerDetails,
                'Spouse Details' => $this->onlyFilled($registration->only(['spouse_tin', 'spouse_name', 'spouse_maiden_name', 'spouse_personal_id'])),
                'Mobile Money Details' => $mobileMoneyDetails,
                'Banking Details' => $bankingDetails,
                'Mobile Money And Banking' => $this->displayFields($registration->only(['mobile_money_type', 'mobile_money_number', 'bank_name', 'bank_country'])),
                'Declaration' => $this->onlyFilled($registration->only(['printed_name', 'declaration_accepted', 'remarks', 'amendment_notes', 'amendment_submitted_at'])),
            ],
        ]);
    }

    private function formatBusinessRegistration(BusinessRegistration $registration): array
    {
        return array_merge($this->formatBusinessRegistrationRow($registration), [
            'title' => '',
            'surname' => '',
            'forenames' => $registration->display_name,
            'business_type' => $registration->business_type_display ?: $registration->business_type,
            'application_type' => $registration->application_type,
            'registration_number' => $registration->registration_number,
            'phone_details' => $registration->phone_details ?? [],
            'banking_details' => [],
            'mobile_money_details' => [],
            'employers' => [],
            'files' => $registration->files ?? [],
            'remarks' => $registration->review_notes ?: $registration->rejection_reason,
            'assignment_history' => $registration->histories ?? [],
            'timeline' => $this->formatTimeline($registration),
            'sections' => [
                'Reference' => $this->displayFields($registration->only(['id', 'reference_number', 'application_type', 'registration_type', 'document_locator', 'receive_date', 'old_tin', 'new_tin', 'status', 'person_id'])),
                'Business Details' => $this->displayFields($registration->only(['legal_name', 'business_type', 'business_type_display', 'title', 'registration_number', 'is_sole_trader', 'name_structure'])),
                'Addresses' => $this->displayFields($registration->only(['structured_postal_address', 'structured_physical_address'])),
                'Contact Details' => $this->displayFields($registration->only(['email', 'primary_phone', 'phone_details', 'structured_phones'])),
                'Trade Details' => $this->displayFields($registration->only(['trade_details', 'principal_details', 'directors_partners'])),
                'Bank And Mobile Money' => $this->displayFields($registration->only(['bank_mobile_money'])),
                'Accountant And Officer' => $this->displayFields($registration->only(['accountant_details', 'nominated_officer_details'])),
                'Sole Trader And Identification' => $this->displayFields($registration->only(['personal_identification', 'sole_trader_details'])),
                'Attachments Summary' => $this->displayFields($registration->only(['file_attachments', 'proof_of_trading_files_count', 'contract_vat_files_count', 'has_antenuptial_file'])),
                'Declaration' => $this->displayFields($registration->only(['declaration_accepted', 'declaration_name', 'declaration_capacity', 'declaration_signature', 'declaration_date'])),
                'Review' => $this->displayFields($registration->only(['review_notes', 'reviewed_at', 'approved_at', 'rejected_at', 'rejection_reason', 'sms_sent', 'sms_status', 'sms_error'])),
            ],
        ]);
    }

    private function formatBusinessAmendment(BusinessAmendment $amendment): array
    {
        return array_merge($this->formatBusinessAmendmentRow($amendment), [
            'title' => '',
            'surname' => '',
            'forenames' => $this->getBusinessAmendmentName($amendment),
            'business_type' => $amendment->registration?->business_type_display ?: $amendment->registration?->business_type,
            'application_type' => $amendment->application_type,
            'registration_number' => $amendment->registration?->registration_number,
            'phone_details' => $amendment->registration?->phone_details ?? [],
            'banking_details' => [],
            'mobile_money_details' => [],
            'employers' => [],
            'files' => $amendment->files ?? [],
            'remarks' => $amendment->review_notes ?: $amendment->rejection_reason,
            'amended_sections' => $amendment->amended_sections_display,
            'amendment_data' => $amendment->amendment_data,
            'assignment_history' => $amendment->histories ?? [],
            'timeline' => $this->formatTimeline($amendment),
            'sections' => [
                'Reference' => $this->displayFields(array_merge($amendment->only(['id', 'reference_number', 'document_locator', 'receive_date', 'application_type', 'amendment_type', 'tin', 'amendment_tin', 'status']), [
                    'etpm_tin' => data_get($amendment->amendment_data, 'etpm_tin') ?? $amendment->amendment_tin,
                    'legacy_tin' => $amendment->tin,
                ])),
                'Linked Business' => $this->onlyFilled([
                    'business_reference' => $amendment->registration?->reference_number,
                    'business_name' => $this->getBusinessAmendmentName($amendment),
                    'business_type' => $amendment->registration?->business_type_display ?: $amendment->registration?->business_type,
                    'registration_number' => $amendment->registration?->registration_number,
                    'email' => $this->getBusinessAmendmentEmail($amendment),
                ]),
                'Amended Sections' => $this->displayFields([
                    'sections' => $amendment->amended_sections,
                    'sections_display' => $amendment->amended_sections_display,
                ]),
                'Amendment Data' => $this->displayFields((array) $amendment->amendment_data),
                'Review' => $this->displayFields($amendment->only(['review_notes', 'reviewed_at', 'approved_at', 'rejected_at', 'rejection_reason', 'applied_at'])),
            ],
        ]);
    }

    private function getIndividualFullName(TinRegistration $registration): string
    {
        return trim($registration->title . ' ' . $registration->forenames . ' ' . $registration->surname);
    }

    private function onlyFilled(array $values): array
    {
        return collect($values)
            ->reject(fn ($value) => $value === null || $value === '' || $value === [])
            ->all();
    }

    private function displayFields(array $values): array
    {
        return collect($values)
            ->map(fn ($value, $key) => $this->displayValue((string) $key, $value))
            ->reject(fn ($value) => $value === null || $value === '' || $value === [])
            ->all();
    }

    private function displayValue(string $key, mixed $value): mixed
    {
        $key = \Illuminate\Support\Str::snake($key);

        if ($value === null || $value === '') {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn ($item, $itemKey) => $this->displayValue((string) $itemKey, $item))
                ->all();
        }

        $normalized = strtoupper(trim((string) $value));

        if (str_contains($key, 'country')) {
            return $this->countryName($normalized);
        }

        if (str_contains($key, 'district')) {
            return $this->districtName($normalized);
        }

        if ($key === 'marital_status') {
            return [
                'SING' => 'Single',
                'MARR' => 'Married',
                'DIVO' => 'Divorced',
                'SEPA' => 'Separated',
                'WIDO' => 'Widowed',
            ][$normalized] ?? (string) $value;
        }

        if (in_array($key, ['condition_of_marriage', 'marriage_condition'], true)) {
            return [
                'ANTE' => 'Ante-nuptial contract',
                'COMM' => 'Community of property',
            ][$normalized] ?? (string) $value;
        }

        if ($key === 'phone_type') {
            return [
                'CEL1' => 'Mobile 1',
                'CEL2' => 'Mobile 2',
                'HOME' => 'Home',
                'WORK' => 'Work',
                'OFC' => 'Office',
                'TEL' => 'Telephone',
                'FAX' => 'Fax',
            ][$normalized] ?? (string) $value;
        }

        if ($key === 'mobile_money_type' || $key === 'mobile_money') {
            return [
                'MPESA' => 'M-Pesa',
                'ECOCASH' => 'EcoCash',
                'ECO' => 'EcoCash',
            ][$normalized] ?? (string) $value;
        }

        return $value;
    }

    private function countryName(string $code): string
    {
        $countries = [
            'LS' => 'Lesotho',
            'ZA' => 'South Africa',
            'BW' => 'Botswana',
            'SZ' => 'Eswatini',
            'NA' => 'Namibia',
            'ZW' => 'Zimbabwe',
            'MZ' => 'Mozambique',
            'MW' => 'Malawi',
            'ZM' => 'Zambia',
            'US' => 'United States',
            'GB' => 'United Kingdom',
        ];

        if (isset($countries[$code])) {
            return $countries[$code];
        }

        if (class_exists(\Locale::class) && preg_match('/^[A-Z]{2}$/', $code)) {
            $name = \Locale::getDisplayRegion('-' . $code, 'en');
            if ($name !== '' && $name !== $code) {
                return $name;
            }
        }

        return $code;
    }

    private function districtName(string $value): string
    {
        $districts = [
            'BB' => 'Butha-Buthe',
            'BEREA' => 'Berea',
            'LERIBE' => 'Leribe',
            'MAFETENG' => 'Mafeteng',
            'MASERU' => 'Maseru',
            'MH' => "Mohale's Hoek",
            'MOHALES HOEK' => "Mohale's Hoek",
            "MOHALE'S HOEK" => "Mohale's Hoek",
            'MOKHOTLONG' => 'Mokhotlong',
            'QN' => "Qacha's Nek",
            'QACHAS NEK' => "Qacha's Nek",
            "QACHA'S NEK" => "Qacha's Nek",
            'QUTHING' => 'Quthing',
            'TT' => 'Thaba-Tseka',
            'THABA TSEKA' => 'Thaba-Tseka',
            'THABA-TSEKA' => 'Thaba-Tseka',
        ];

        return $districts[$value] ?? ucwords(strtolower(str_replace('_', ' ', $value)));
    }

    private function isIndividualAmendment(TinRegistration $registration): bool
    {
        return in_array($registration->registration_type, ['AMND', 'AMEND'], true);
    }

    private function sendIndividualApprovalSms(TinRegistration $registration, SmsService $smsService, bool $isAmendment): array
    {
        $phoneNumber = $this->getIndividualPhoneNumber($registration);
        if (!$phoneNumber) {
            return ['success' => false, 'message' => 'No phone number found'];
        }

        return $isAmendment
            ? $smsService->sendAmendmentNotification($phoneNumber, $this->getIndividualFullName($registration))
            : $smsService->sendTinNotification($phoneNumber, (string) $registration->tin, $this->getIndividualFullName($registration));
    }

    private function sendIndividualRejectionSms(TinRegistration $registration, SmsService $smsService, string $reason): array
    {
        $phoneNumber = $this->getIndividualPhoneNumber($registration);
        if (!$phoneNumber) {
            return ['success' => false, 'message' => 'No phone number found'];
        }

        return $smsService->sendRejectionNotification($phoneNumber, $this->getIndividualFullName($registration), $reason);
    }

    private function getIndividualPhoneNumber(TinRegistration $registration): ?string
    {
        $phoneDetail = $registration->phoneDetails->first();
        if ($phoneDetail?->phone_number) {
            return $this->normalisePhoneNumber($phoneDetail->phone_number, $phoneDetail->phone_code);
        }

        $phone = $registration->phone_number;

        return $phone ? $this->normalisePhoneNumber($phone, $registration->phone_code) : null;
    }

    private function sendBusinessApprovalSms(BusinessRegistration $registration, SmsService $smsService): array
    {
        $phoneNumber = $this->getBusinessPhoneNumber($registration);
        if (!$phoneNumber) {
            return ['success' => false, 'message' => 'No phone number found'];
        }

        return $smsService->sendBusinessRegistrationNotification(
            $phoneNumber,
            $registration->display_name,
            (string) $registration->new_tin
        );
    }

    private function sendBusinessAmendmentApprovalSms(BusinessAmendment $amendment, SmsService $smsService): array
    {
        $phoneNumber = $this->getBusinessAmendmentPhoneNumber($amendment);
        if (!$phoneNumber) {
            return ['success' => false, 'message' => 'No phone number found'];
        }

        return $smsService->sendSMS(
            $phoneNumber,
            'Dear Client, your business registration amendment has been approved. Thank for using RSL App.'
        );
    }

    private function sendBusinessRejectionSms(BusinessRegistration $registration, SmsService $smsService, string $reason): array
    {
        $phoneNumber = $this->getBusinessPhoneNumber($registration);
        if (!$phoneNumber) {
            return ['success' => false, 'message' => 'No phone number found'];
        }

        return $smsService->sendRejectionNotification($phoneNumber, $registration->display_name, $reason);
    }

    private function sendBusinessAmendmentRejectionSms(BusinessAmendment $amendment, SmsService $smsService, string $reason): array
    {
        $phoneNumber = $this->getBusinessAmendmentPhoneNumber($amendment);
        if (!$phoneNumber) {
            return ['success' => false, 'message' => 'No phone number found'];
        }

        return $smsService->sendRejectionNotification($phoneNumber, $this->getBusinessAmendmentName($amendment), $reason);
    }

    private function getBusinessAmendmentPhoneNumber(BusinessAmendment $amendment): ?string
    {
        $phone = data_get($amendment->amendment_data, 'contact_info.phone_details.0.phone_number')
            ?? data_get($amendment->amendment_data, 'contact_info.phone_details.0.phoneNumber');

        if ($phone) {
            return $this->normalisePhoneNumber($phone, data_get($amendment->amendment_data, 'contact_info.phone_details.0.phone_code'));
        }

        return $amendment->registration ? $this->getBusinessPhoneNumber($amendment->registration) : null;
    }

    private function getBusinessPhoneNumber(BusinessRegistration $registration): ?string
    {
        if ($registration->primary_phone) {
            return trim((string) $registration->primary_phone);
        }

        foreach (($registration->phone_details ?? []) as $phone) {
            $number = $phone['phoneNumber'] ?? $phone['phone_number'] ?? null;
            if ($number) {
                return $this->normalisePhoneNumber($number, $phone['phoneCode'] ?? $phone['phone_code'] ?? null);
            }
        }

        foreach (($registration->structured_phones ?? []) as $phone) {
            $number = $phone['phoneNumber'] ?? $phone['phone_number'] ?? null;
            if ($number) {
                return $this->normalisePhoneNumber($number, $phone['phoneCode'] ?? $phone['phone_code'] ?? null);
            }
        }

        return null;
    }

    private function normalisePhoneNumber(mixed $number, mixed $code = null): ?string
    {
        if (is_array($number)) {
            $number = $number['text'] ?? null;
        }

        if (!$number) {
            return null;
        }

        $number = trim((string) $number);
        $code = $code ? trim((string) $code) : '';

        if ($code && !str_starts_with($number, $code)) {
            return $code . $number;
        }

        return $number;
    }

    private function logOperation($subject, string $eventType, ?string $channel, string $status, string $title, ?string $message = null, array $metadata = []): void
    {
        RegistrationOperationEvent::create([
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'subject_label' => $this->subjectLabel($subject),
            'event_type' => $eventType,
            'channel' => $channel,
            'status' => $status,
            'title' => $title,
            'message' => $message,
            'user_id' => auth()->id(),
            'metadata' => $metadata,
        ]);
    }

    private function subjectLabel($subject): string
    {
        return match (true) {
            $subject instanceof TinRegistration => (string) ($subject->ref ?: $subject->id),
            $subject instanceof BusinessRegistration => (string) ($subject->reference_number ?: $subject->id),
            $subject instanceof BusinessAmendment => (string) ($subject->reference_number ?: $subject->id),
            default => (string) $subject->id,
        };
    }

    private function formatTimeline($subject): array
    {
        return RegistrationOperationEvent::where('subject_type', get_class($subject))
            ->where('subject_id', $subject->id)
            ->with('user')
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn ($event) => [
                'type' => $event->event_type,
                'channel' => $event->channel,
                'status' => $event->status,
                'title' => $event->title,
                'message' => $event->message,
                'user' => $event->user?->name ?: 'System',
                'created_at' => $event->created_at?->format('Y-m-d H:i:s'),
            ])
            ->values()
            ->all();
    }

    private function latestAssignmentTime($subject): string
    {
        if (isset($subject->assigned_at) && $subject->assigned_at) {
            return $subject->assigned_at->format('Y-m-d H:i:s');
        }

        $event = RegistrationOperationEvent::where('subject_type', get_class($subject))
            ->where('subject_id', $subject->id)
            ->where('event_type', 'action')
            ->where('title', 'Assigned to user')
            ->latest()
            ->first();

        return $event?->created_at?->format('Y-m-d H:i:s') ?? 'N/A';
    }

    private function resolveAiSubject(string $type, int $id)
    {
        return match ($type) {
            'individual', 'individual_amendment' => TinRegistration::with(['files', 'phoneDetails', 'bankingDetails', 'mobileMoneyDetails', 'employers'])->findOrFail($id),
            'business' => BusinessRegistration::findOrFail($id),
            'business_amendment' => BusinessAmendment::with('registration')->findOrFail($id),
            default => abort(404),
        };
    }

    private function aiCaseContext($subject): array
    {
        $events = collect($this->formatTimeline($subject));

        return [
            'record_type' => class_basename($subject),
            'workflow' => [
                'status' => $subject instanceof TinRegistration ? $subject->status : $this->displayStatusFromBusiness($subject->status),
                'assigned' => !empty($subject->assigned_to),
                'age_days' => $subject->created_at ? (int) $subject->created_at->diffInDays(now()) : null,
                'submitted_at' => $subject->created_at?->toDateString(),
            ],
            'registration_shape' => [
                'is_amendment' => $subject instanceof BusinessAmendment || ($subject instanceof TinRegistration && $this->isIndividualAmendment($subject)),
                'registration_type' => $subject->registration_type ?? $subject->application_type ?? null,
                'business_type' => $subject instanceof BusinessRegistration ? $subject->business_type : null,
                'amended_sections' => $subject instanceof BusinessAmendment ? $subject->amended_sections_display : null,
            ],
            'data_completeness' => [
                'has_email' => $this->subjectHasEmail($subject),
                'has_phone' => $this->subjectHasPhone($subject),
                'attachment_count' => $this->subjectAttachmentCount($subject),
                'has_bank_details' => $subject instanceof TinRegistration ? $subject->bankingDetails->isNotEmpty() : null,
            ],
            'operations' => [
                'event_count' => $events->count(),
                'latest_events' => $events->take(8)->map(fn ($event) => [
                    'type' => $event['type'] ?? null,
                    'channel' => $event['channel'] ?? null,
                    'status' => $event['status'] ?? null,
                    'title' => $event['title'] ?? null,
                    'message' => $event['message'] ?? null,
                    'created_at' => $event['created_at'] ?? null,
                ])->values()->all(),
                'failed_soap_count' => $events->where('type', 'soap')->where('status', 'failed')->count(),
                'failed_sms_count' => $events->where('type', 'sms')->where('status', 'failed')->count(),
            ],
        ];
    }

    private function subjectHasEmail($subject): bool
    {
        if ($subject instanceof BusinessAmendment) {
            return $this->getBusinessAmendmentEmail($subject) !== 'N/A';
        }

        return !empty($subject->email);
    }

    private function subjectHasPhone($subject): bool
    {
        return match (true) {
            $subject instanceof TinRegistration => (bool) $this->getIndividualPhoneNumber($subject),
            $subject instanceof BusinessRegistration => (bool) $this->getBusinessPhoneNumber($subject),
            $subject instanceof BusinessAmendment => (bool) $this->getBusinessAmendmentPhoneNumber($subject),
            default => false,
        };
    }

    private function subjectAttachmentCount($subject): int
    {
        if (method_exists($subject, 'files')) {
            return $subject->relationLoaded('files') ? $subject->files->count() : $subject->files()->count();
        }

        return is_countable($subject->files ?? null) ? count($subject->files) : 0;
    }

    private function safeSoapMetadata(array $metadata): array
    {
        return collect($metadata)
            ->only(['success', 'message', 'error_message', 'mock', 'status', 'retry_of'])
            ->filter(fn ($value) => !is_array($value) && !is_object($value))
            ->all();
    }

    private function slaRow($item, string $type, string $ref, string $name, string $status): array
    {
        $ageDays = (int) $item->created_at->diffInDays(now());

        return [
            'ref' => $ref,
            'type' => $type,
            'name' => $name ?: 'N/A',
            'status' => $status,
            'assigned_to' => $this->getAssignedToName($item->assigned_to),
            'age_days' => $ageDays,
            'overdue' => $ageDays >= 3,
            'due_soon' => $ageDays === 2,
        ];
    }

    private function getBusinessAmendmentName(BusinessAmendment $amendment): string
    {
        return $amendment->registration?->display_name
            ?? data_get($amendment->amendment_data, 'business_details.legal_name')
            ?? data_get($amendment->amendment_data, 'legal_name')
            ?? data_get($amendment->amendment_data, 'business_name')
            ?? 'Business Amendment';
    }

    private function getBusinessAmendmentEmail(BusinessAmendment $amendment): string
    {
        return $amendment->registration?->email
            ?? data_get($amendment->amendment_data, 'contact_info.email')
            ?? data_get($amendment->amendment_data, 'email')
            ?? 'N/A';
    }

    private function getAssignedToName(?string $assignedTo): string
    {
        if (empty($assignedTo)) {
            return 'Unassigned';
        }

        return User::find($assignedTo)?->name ?? 'Unknown';
    }

    private function displayStatusFromBusiness(string $status): string
    {
        return match ($status) {
            'submitted' => 'PENDING',
            'under_review' => 'UNDER_REVIEW',
            'approved' => 'APPROVED',
            'rejected' => 'REJECTED',
            default => strtoupper($status),
        };
    }

    private function businessStatusFromDisplay(string $status): string
    {
        return match (strtoupper($status)) {
            'PENDING' => 'submitted',
            'UNDER_REVIEW' => 'under_review',
            'APPROVED' => 'approved',
            'REJECTED' => 'rejected',
            default => strtolower($status),
        };
    }

    private function unassignedIndividualQuery()
    {
        return TinRegistration::where('status', 'PENDING')->where(function ($query) {
            $query->whereNull('assigned_to')->orWhere('assigned_to', '');
        });
    }

    private function unassignedBusinessQuery()
    {
        return BusinessRegistration::where('status', 'submitted')->where(function ($query) {
            $query->whereNull('assigned_to')->orWhere('assigned_to', '');
        });
    }

    private function unassignedBusinessAmendmentQuery()
    {
        return BusinessAmendment::where('status', 'submitted')->where(function ($query) {
            $query->whereNull('assigned_to')->orWhere('assigned_to', '');
        });
    }

    private function totalIndividualRegistrationCount(string $scope, bool $amendmentsOnly = false): int
    {
        if ($scope === 'unassigned') {
            $query = $this->unassignedIndividualQuery();
            $this->applyIndividualRegistrationTypeScope($query, $amendmentsOnly);

            return $query->count();
        }

        $query = TinRegistration::query();
        $this->applyIndividualRegistrationTypeScope($query, $amendmentsOnly);

        return $query->count();
    }

    private function totalBusinessRegistrationCount(string $scope): int
    {
        if ($scope === 'unassigned') {
            return $this->unassignedBusinessQuery()->count();
        }

        return BusinessRegistration::count();
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], 403);
    }
}
