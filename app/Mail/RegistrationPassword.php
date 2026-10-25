<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class RegistrationPassword extends Mailable
{
    public function __construct(
        public string $phone,
        public string $temporaryPassword,
        public string $verifyUrl,
    ) {}

    public function build()
    {
        return $this->subject('BOSH — ваш пароль для входа')->text('mail.registration-password');
    }
}
