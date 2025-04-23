<?php


class ValidateUrl
{
    public function __construct()
    {

    }

    public function process($url)
    {
        if (!$url || !$this->validateUrl($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'status' => 'error',
                'message' => 'Tautan tidak valid',
                'data' => null,
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Memproses Tautan...',
            'data' => [
                'url' => $url
            ],
        ];
    }

    private function validateUrl($url)
    {
        return (preg_match('#/f/([a-zA-Z0-9]+)#', $url) || strpos($url, 'justpaste') !== false);
    }

}