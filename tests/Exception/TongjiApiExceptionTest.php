<?php

namespace Tourze\BaiduTongjiApiBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\BaiduTongjiApiBundle\Exception\TongjiApiException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(TongjiApiException::class)]
final class TongjiApiExceptionTest extends AbstractExceptionTestCase
{
    public function testAccessTokenExpired(): void
    {
        $exception = TongjiApiException::accessTokenExpired();

        $this->assertInstanceOf(TongjiApiException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Access token expired', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }

    public function testHttpError(): void
    {
        $message = '404 Not Found';
        $exception = TongjiApiException::httpError($message);

        $this->assertInstanceOf(TongjiApiException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('HTTP request failed: ' . $message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }

    public function testHttpErrorWithEmptyMessage(): void
    {
        $exception = TongjiApiException::httpError('');

        $this->assertSame('HTTP request failed: ', $exception->getMessage());
    }

    public function testInvalidResponse(): void
    {
        $message = 'JSON decode error';
        $exception = TongjiApiException::invalidResponse($message);

        $this->assertInstanceOf(TongjiApiException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Invalid API response: ' . $message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }

    public function testInvalidResponseWithEmptyMessage(): void
    {
        $exception = TongjiApiException::invalidResponse('');

        $this->assertSame('Invalid API response: ', $exception->getMessage());
    }

    public function testApiError(): void
    {
        $message = 'Invalid site ID';
        $exception = TongjiApiException::apiError($message);

        $this->assertInstanceOf(TongjiApiException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Baidu Tongji API error: ' . $message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }

    public function testApiErrorWithEmptyMessage(): void
    {
        $exception = TongjiApiException::apiError('');

        $this->assertSame('Baidu Tongji API error: ', $exception->getMessage());
    }

    public function testExceptionInheritance(): void
    {
        $exception = TongjiApiException::accessTokenExpired();

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testStaticMethodsReturnDifferentInstances(): void
    {
        $exception1 = TongjiApiException::accessTokenExpired();
        $exception2 = TongjiApiException::accessTokenExpired();

        $this->assertNotSame($exception1, $exception2);
        $this->assertEquals($exception1->getMessage(), $exception2->getMessage());
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(TongjiApiException::class);
        $this->expectExceptionMessage('Access token expired');

        throw TongjiApiException::accessTokenExpired();
    }

    public function testExceptionCanBeCaught(): void
    {
        $caught = false;

        try {
            throw TongjiApiException::httpError('Connection timeout');
        } catch (TongjiApiException $e) {
            $this->assertSame('HTTP request failed: Connection timeout', $e->getMessage());
            $caught = true;
        }

        $this->assertTrue($caught, 'Exception should have been caught');
    }

    public function testMessageFormatting(): void
    {
        $testCases = [
            [TongjiApiException::accessTokenExpired(), 'Access token expired'],
            [TongjiApiException::httpError('Connection failed'), 'HTTP request failed: Connection failed'],
            [TongjiApiException::invalidResponse('Malformed JSON'), 'Invalid API response: Malformed JSON'],
            [TongjiApiException::apiError('Rate limit exceeded'), 'Baidu Tongji API error: Rate limit exceeded'],
        ];

        foreach ($testCases as [$exception, $expectedMessage]) {
            $this->assertSame($expectedMessage, $exception->getMessage(), 'Exception should format message correctly');
        }
    }

    public function testAllFactoryMethodsReturnSameExceptionType(): void
    {
        $exceptions = [
            TongjiApiException::accessTokenExpired(),
            TongjiApiException::httpError('test'),
            TongjiApiException::invalidResponse('test'),
            TongjiApiException::apiError('test'),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(TongjiApiException::class, $exception);
        }
    }

    public function testExceptionWithSpecialCharacters(): void
    {
        $specialMessage = 'Error with "quotes" and \'apostrophes\' and \backslashes';
        $exception = TongjiApiException::apiError($specialMessage);

        $this->assertStringContainsString($specialMessage, $exception->getMessage());
    }

    public function testExceptionWithUnicodeCharacters(): void
    {
        $unicodeMessage = '错误：API调用失败';
        $exception = TongjiApiException::apiError($unicodeMessage);

        $this->assertStringContainsString($unicodeMessage, $exception->getMessage());
    }
}
