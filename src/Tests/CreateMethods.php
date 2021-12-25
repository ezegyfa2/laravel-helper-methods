<?php

namespace Ezegyfa\LaravelHelperMethods\Tests;

trait CreateMethods
{
    use TestMethods;

    protected $createUrl;

    public function assertCreateWithMissingFormData(array $postData, array $expectedMissingFieldNames)
    {
        $this->assertMissingFormDataPost($this->createUrl, $postData, $expectedMissingFieldNames);
    }

    protected function assertCreatePost(array $postData, array $expectedResponseData, array $databaseRowAsserters)
    {
        $this->assertPost($this->createUrl, $postData, $expectedResponseData);
        $this->assertDatabaseRows($databaseRowAsserters);
    }

    protected function assertCreateWithInvalidFormData(array $postData, array $expectedErrors)
    {
        $this->assertInvalidFormDataPost($this->createUrl, $postData, $expectedErrors);
    }

    protected function assertInvalidCreatePost(array $postData, array $expectedResponseContent, int $statusCode = 422)
    {
        $this->assertInvalidPost($this->createUrl, $postData, $expectedResponseContent, $statusCode);
    }
}
