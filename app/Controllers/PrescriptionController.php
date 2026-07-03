<?php
require_once __DIR__ . '/../Services/PrescriptionService.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Helpers/Response.php';
require_once __DIR__ . '/../Helpers/Validator.php';

class PrescriptionController {

    // GET /prescriptions
    // - patient: only their own records (scoped by patient.user_id)
    // - doctor:  only prescriptions they created (scoped by doctor_id)
    // - pharmacist: all (read-only)
    public static function index(): void {
        $auth = AuthMiddleware::handle();
        AuthMiddleware::allowRoles($auth, ['doctor', 'pharmacist', 'patient']);

        if ($auth['role'] === 'patient') {
            $list = PrescriptionService::getForLoggedInPatient((int) $auth['user_id']);
        } elseif ($auth['role'] === 'doctor') {
            $list = PrescriptionService::getForDoctor((int) $auth['user_id']);
        } else {
            // pharmacist — sees all
            $list = PrescriptionService::getAll();
        }
        Response::success('Prescriptions retrieved', $list);
    }

    // GET /prescriptions/{id}
    public static function show(int $id): void {
        $auth = AuthMiddleware::handle();
        AuthMiddleware::allowRoles($auth, ['doctor', 'pharmacist', 'patient']);

        $rx = PrescriptionService::getById($id);

        if ($auth['role'] === 'patient') {
            PrescriptionService::assertOwnedByUser($rx, (int) $auth['user_id']);
        }

        if ($auth['role'] === 'doctor') {
            PrescriptionService::assertOwnedByDoctor($rx, (int) $auth['user_id']);
        }

        Response::success('Prescription retrieved', $rx);
    }

    // GET /patients/{id}/prescriptions
    public static function byPatient(int $patientId): void {
        $auth = AuthMiddleware::handle();
        AuthMiddleware::allowRoles($auth, ['doctor', 'pharmacist']);
        $list = PrescriptionService::getByPatient($patientId);
        Response::success('Patient prescriptions retrieved', $list);
    }

    // GET /appointments/{id}/prescription
    public static function byAppointment(int $appointmentId): void {
        $auth = AuthMiddleware::handle();
        AuthMiddleware::allowRoles($auth, ['doctor', 'pharmacist']);
        $rx = PrescriptionService::getByAppointment($appointmentId);
        Response::success('Appointment prescription retrieved', $rx);
    }

    // POST /prescriptions — only doctors can create
    public static function store(): void {
        $auth = AuthMiddleware::handle();
        AuthMiddleware::allowRoles($auth, ['doctor']);

        $payload = json_decode(file_get_contents('php://input'), true);
        $v = new Validator($payload);
        $v->required('patient_id')
          ->required('appointment_id')
          ->required('medicines');

        if ($v->fails()) {
            Response::error(implode(', ', $v->errors()), 400);
        }

        $rx = PrescriptionService::create($payload, (int) $auth['user_id']);
        Response::success('Prescription created', $rx, 201);
    }

    // PUT /prescriptions/{id} — only the doctor who created it can edit
    public static function update(int $id): void {
        $auth = AuthMiddleware::handle();
        AuthMiddleware::allowRoles($auth, ['doctor']);

        $rx = PrescriptionService::getById($id);
        PrescriptionService::assertOwnedByDoctor($rx, (int) $auth['user_id']);

        $payload = json_decode(file_get_contents('php://input'), true);
        if (empty($payload)) {
            Response::error('No data provided', 400);
        }

        $rx = PrescriptionService::update($id, $payload);
        Response::success('Prescription updated', $rx);
    }

    // PATCH /prescriptions/{id}/status
    // - pharmacist: can mark as 'verified' or 'dispensed'
    // - doctor: NOT allowed to change status (they can only create/edit/delete)
    public static function updateStatus(int $id): void {
        $auth = AuthMiddleware::handle();
        AuthMiddleware::allowRoles($auth, ['pharmacist']);

        $payload = json_decode(file_get_contents('php://input'), true);
        if (empty($payload['status'])) {
            Response::error('Status is required', 400);
        }

        $allowed = ['verified', 'dispensed'];
        if (!in_array($payload['status'], $allowed)) {
            Response::error('Pharmacist can only set status to: ' . implode(', ', $allowed), 400);
        }

        $rx = PrescriptionService::updateStatus($id, $payload['status']);
        Response::success('Prescription status updated', $rx);
    }

    // DELETE /prescriptions/{id} — only the doctor who created it can delete
    public static function destroy(int $id): void {
        $auth = AuthMiddleware::handle();
        AuthMiddleware::allowRoles($auth, ['doctor']);

        $rx = PrescriptionService::getById($id);
        PrescriptionService::assertOwnedByDoctor($rx, (int) $auth['user_id']);

        PrescriptionService::delete($id);
        Response::success('Prescription deleted');
    }
}