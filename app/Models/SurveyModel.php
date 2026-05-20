<?php

namespace App\Models;

use CodeIgniter\Model;

class SurveyModel extends Model
{
    protected $table = 'booking_survey_responses';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $allowedFields = [
        'booking_id',
        'survey_token',
        'is_submitted',
        'created_at',
        'updated_at',
        // STAFF SECTION
        'staff_punctuality',
        'staff_courtesy_property',
        'staff_courtesy_audio',
        'staff_courtesy_janitor',
        // FACILITY SECTION
        'facility_level_expectations',
        'facility_cleanliness',
        'facility_maintenance',
        // VENUE ACCURACY SECTION
        'venue_accuracy_setup',
        'venue_accuracy_space',
        // CATERING SECTION
        'catering_quality',
        'catering_presentation',
        'catering_service',
        // OVERALL EXPERIENCE
        'overall_satisfaction',
        'most_enjoyed',
        'improvements_needed',
        'recommendation'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get survey response by booking ID
     */
    public function getByBookingId($bookingId)
    {
        return $this->where('booking_id', $bookingId)->first();
    }

    /**
     * Get survey response by survey token
     */
    public function getByToken($token)
    {
        return $this->where('survey_token', $token)->first();
    }

    /**
     * Check if booking has a survey
     */
    public function hasSurvey($bookingId)
    {
        return $this->where('booking_id', $bookingId)->countAllResults() > 0;
    }

    /**
     * Create a new survey response
     */
    public function createSurvey($bookingId, $surveyData)
    {
        $data = [
            'booking_id' => $bookingId,
            'survey_token' => $this->generateToken(),
        ];

        // Merge survey data
        $data = array_merge($data, $surveyData);

        return $this->insert($data);
    }

    /**
     * Update survey response
     */
    public function updateSurvey($bookingId, $surveyData)
    {
        log_message('debug', 'updateSurvey called - Booking ID: ' . $bookingId . ', Data keys: ' . json_encode(array_keys($surveyData)));
        
        // Ensure we only use allowed fields
        $filteredData = [];
        foreach ($surveyData as $key => $value) {
            if (in_array($key, $this->allowedFields)) {
                $filteredData[$key] = $value;
            }
        }
        
        log_message('debug', 'Filtered data for update: ' . json_encode($filteredData));
        
        if (empty($filteredData)) {
            log_message('error', 'No valid data after filtering in updateSurvey');
            return false;
        }
        
        try {
            $result = $this->where('booking_id', $bookingId)->update(NULL, $filteredData);
            log_message('info', 'Update result: ' . ($result ? 'Success' : 'Failed'));
            return $result;
        } catch (\Exception $e) {
            log_message('error', 'Update exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate a unique survey token
     */
    public function generateToken()
    {
        $token = bin2hex(random_bytes(32));
        
        // Ensure it's unique
        while ($this->where('survey_token', $token)->countAllResults() > 0) {
            $token = bin2hex(random_bytes(32));
        }

        return $token;
    }

    /**
     * Get all survey responses (admin)
     */
    public function getAllSurveys($limit = null, $offset = 0)
    {
        $query = $this->select('bs.*, b.client_name, b.email_address, f.name as facility_name')
            ->from('booking_survey_responses bs')
            ->join('bookings b', 'b.id = bs.booking_id')
            ->join('facilities f', 'f.id = b.facility_id')
            ->orderBy('bs.created_at', 'DESC');

        if ($limit) {
            $query->limit($limit, $offset);
        }

        return $query->get()->getResultArray();
    }

    /**
     * Get survey statistics
     */
    public function getSurveyStats()
    {
        return $this->select('
            COUNT(*) as total_responses,
            SUM(CASE WHEN staff_punctuality = "Excellent" THEN 1 ELSE 0 END) as excellent_staff,
            SUM(CASE WHEN facility_level_expectations = "Excellent" THEN 1 ELSE 0 END) as excellent_facility,
            SUM(CASE WHEN overall_would_recommend = "Yes" THEN 1 ELSE 0 END) as would_recommend
        ')
        ->first();
    }
}
