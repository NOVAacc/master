<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class RegistrationPassword extends Mailable
{
    public function __construct(
        public string $name,
        public string $phone,
        public string $temporaryPassword,
        public string $verifyUrl,
    ) {}

    public function build()
    {
        return $this->subject('BOSH — подтвердите почту и начните работу')->view('mail.registration-password')->text('mail.registration-password-text');
    }
}
