<?php

namespace Helpers\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\TestResponse;
use function PHPUnit\Framework\assertEquals;

trait TestMethods
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->loginSetup();
    }

    protected function tearDown(): void
    {
        Artisan::call('migrate:rollback');
        $this->loginToken = null;
        parent::tearDown();
    }

    protected function assertGet(string $url, array $expectedResponseData, string $loginToken = null)
    {
        $response = $this->jsonGet($url, $loginToken);
        $this->assertResponse($response, [
            'data' => $expectedResponseData
        ], 200);
    }

    protected function jsonGet(string $url, string $loginToken = null)
    {
        return $this->get($url, [
            'Authorization' => 'Bearer ' . $this->getLoginToken(),
            'Content-Type' => 'multipart/form-data',
            'Accept' => 'application/json',
        ]);
    }

    protected function assertResponse(TestResponse $response, $expectedResponseContent, int $expectedStatusCode = 201)
    {
        $this->assertStatus($response, $expectedStatusCode);
        if (is_array($expectedResponseContent)) {
            $responseContent = json_decode($response->content(), true);
        } else {
            $responseContent = $response->content();
        }
        assertEquals($expectedResponseContent, $responseContent,
            'Actual response content: ' . json_encode($responseContent, JSON_PRETTY_PRINT));
    }

    protected function assertStatus(TestResponse $response, int $expectedStatusCode = 201)
    {
        assertEquals($expectedStatusCode, $response->status(),
            'Invalid status code. Response content: ' . $response->content());
    }

    protected function assertPost(string $url, array $postData, array $expectedResponseData)
    {
        $response = $this->jsonPost($url, $postData);
        $this->assertResponse($response, [
            'data' => $expectedResponseData
        ]);
    }

    protected function jsonPost(string $url, array $data, string $loginToken = null)
    {
        return $this->post($url, $data, [
            'Authorization' => 'Bearer ' . $this->getLoginToken(),
            'Content-Type' => 'multipart/form-data',
            'Accept' => 'application/json',
        ]);
    }

    protected function assertMissingFormDataPost(string $url, array $postData, array $missingFieldNames)
    {
        $errors = [];
        foreach ($missingFieldNames as $missingFieldName) {
            $formattedMissingFieldName = str_replace('_', ' ', $missingFieldName);
            $errors[$missingFieldName] = [
                "The $formattedMissingFieldName field is required.",
            ];
        }
        $this->assertInvalidFormDataPost($url, $postData, $errors);
    }

    protected function assertInvalidFormDataPost(string $url, array $postData, array $expectedErrors)
    {
        $this->assertInvalidPost($url, $postData, [
            'message' => 'The given data was invalid.',
            'errors' => $expectedErrors,
        ], 422);
    }

    protected function assertInvalidPost(
        string $url,
        array $postData,
        $expectedResponseContent,
        int $expectedStatusCode
    ) {
        $response = $this->jsonPost($url, $postData);
        $this->assertResponse($response, $expectedResponseContent, $expectedStatusCode);
    }

    protected function assertInvalidGet(string $url, $expectedResponseContent, int $expectedStatusCode)
    {
        $response = $this->jsonGet($url);
        $this->assertResponse($response, $expectedResponseContent, $expectedStatusCode);
    }

    protected function assertDatabaseRows(array $databaseRowAsserters)
    {
        foreach ($databaseRowAsserters as $rowAsserter) {
            $rowAsserter->assert();
        }
    }
}
