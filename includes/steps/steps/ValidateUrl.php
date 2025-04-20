<?php


class ValidateUrl
{
    public function __construct()
    {

    }

    public function process($url, $step = 1)
    {
        if (!$url || !$this->validateUrl($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'status' => 'error',
                'message' => 'Tautan tidak valid',
                'data' => null,
                'step' => $step
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Memproses Tautan...',
            'data' => [
                'url' => $url
            ],
            'step' => $step
        ];
    }

    private function validateUrl($url)
    {
        return preg_match('/https?:\/\/(.+?)\/(d|e)\/([a-zA-Z0-9]+)/', $url);
    }

}