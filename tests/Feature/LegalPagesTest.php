<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_legal_documents_are_public_and_restricted_to_known_slugs(): void
    {
        foreach (['terms', 'privacy', 'cookies'] as $document) {
            $this->get('/legal/'.$document)
                ->assertOk()
                ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
                    ->component('Legal')->where('document', $document));
        }
        $this->get('/legal/invalid')->assertNotFound();
    }
}
