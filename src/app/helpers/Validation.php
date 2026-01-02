<?php
class Validation {
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function validatePassword($password) {
        return strlen($password) >= 8;
    }
    
    public static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    public static function validateYearMonth($yearMonth) {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $yearMonth);
    }
    
    public static function validateDecimal($value, $min = 0, $max = 100) {
        if (!is_numeric($value)) return false;
        
        $floatVal = floatval($value);
        return $floatVal >= $min && $floatVal <= $max;
    }
    
    public static function validatePercentage($value) {
        return self::validateDecimal($value, 0, 100);
    }
    
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
    
    public static function validateAllocationSum($allocations) {
        $sum = array_sum($allocations);
        return abs($sum - 100.0) < 0.00000001;
    }
    
    public static function validateCSVHeaders($headers, $expectedHeaders) {
        if (count($headers) !== count($expectedHeaders)) {
            return false;
        }
        
        foreach ($expectedHeaders as $index => $expected) {
            if (trim($headers[$index]) !== $expected) {
                return false;
            }
        }
        
        return true;
    }
}