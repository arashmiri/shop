<?php

namespace App\Services;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;

class JwtService
{
    private Configuration $config;

    public function __construct()
    {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText(env('JWT_SECRET', 'default-secret-key')) // مقدار کلید از env خوانده شود
        );
    }

    public function generateToken(string $phone): string
    {
        $now = new \DateTimeImmutable();

        return $this->config->builder()
            ->issuedBy('your-app-name') // نام اپلیکیشن
            ->issuedAt($now)
            ->expiresAt($now->modify('+2 minutes')) // تاریخ انقضا
            ->withClaim('phone', $phone) // اضافه کردن شماره موبایل به توکن
            ->getToken($this->config->signer(), $this->config->signingKey()) // امضای توکن
            ->toString();
    }

    public function validateToken(string $token): ?string
    {
        try {
            $parsedToken = $this->config->parser()->parse($token);
            $constraints = [
                new SignedWith($this->config->signer(), $this->config->signingKey())
            ];

            if (!$this->config->validator()->validate($parsedToken, ...$constraints)) {
                return null; // توکن معتبر نیست
            }

            if ($parsedToken->isExpired(new \DateTimeImmutable())) {
                return null; // توکن منقضی شده است
            }

            return $parsedToken->claims()->get('phone'); // شماره موبایل از توکن استخراج شود
        } catch (\Exception $e) {
            return null;
        }
    }
}
