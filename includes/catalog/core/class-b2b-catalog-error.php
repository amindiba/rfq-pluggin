<?php
defined('ABSPATH') || exit;

class B2B_Catalog_Error {

    private $code;
    private $message;
    private $data;
    private $http_status;

    public function __construct($code, $message, $data = array(), $http_status = 400) {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
        $this->http_status = $http_status;
    }

    public function get_code() {
        return $this->code;
    }

    public function get_message() {
        return $this->message;
    }

    public function get_data() {
        return $this->data;
    }

    public function get_http_status() {
        return $this->http_status;
    }

    public function to_array() {
        return array(
            'code' => $this->code,
            'message' => $this->message,
            'data' => $this->data,
        );
    }

    public static function not_found($message = 'مورد یافت نشد') {
        return new self('not_found', $message, array(), 404);
    }

    public static function validation($errors) {
        return new self('validation_error', 'خطا در اعتبارسنجی', $errors, 400);
    }

    public static function unauthorized($message = 'دسترسی غیرمجاز') {
        return new self('unauthorized', $message, array(), 403);
    }

    public static function internal($message = 'خطای داخلی سرور') {
        return new self('internal_error', $message, array(), 500);
    }

    public static function bad_request($message = 'درخواست نامعتبر') {
        return new self('bad_request', $message, array(), 400);
    }
}
