<?php

namespace App\Mail;

use App\Models\Commande;
use App\Models\ReglagesSite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RappelCommande extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Commande $commande)
    {
        $this->commande->loadMissing(['sessionLivraison', 'pointRelais', 'client']);
    }

    public function envelope(): Envelope
    {
        $settings = ReglagesSite::query()->first();

        return new Envelope(
            subject: $settings?->sujet_email_rappel ?: 'Rappel — votre panier arrive demain',
            replyTo: $settings?->email_contact ? [new Address($settings->email_contact)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.rappel-commande', with: [
            'texteAdditionnel' => ReglagesSite::query()->first()?->texte_email_rappel,
        ]);
    }
}
