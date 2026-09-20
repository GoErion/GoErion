<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Actions\Company\InvitationDelivery;
use App\Mail\CompanyInvitationMail;
use Illuminate\Support\ConfigurationUrlParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendCompanyInvitation
{
    public function handle(InvitationDelivery $delivery): bool
    {
        try {
            if (DB::transactionLevel() !== 0 || (! Mail::isFake() && ! $this->safeMailer(config('mail.default')))) {
                return false;
            }
            $invitation = $delivery->invitation->load(['company', 'invitedBy']);
            $mail = new CompanyInvitationMail($invitation, route('invitations.entry', ['token' => $delivery->takeToken()]));
            $sent = Mail::to($invitation->email)->send($mail);
            if ($sent === null && ! Mail::isFake()) {
                return false;
            }
            $invitation->newQuery()->whereKey($invitation)->where('token_hash', $invitation->token_hash)
                ->update(['last_sent_at' => now('UTC')]);

            return true;
        } catch (Throwable) {
            // Transport exceptions may contain the complete message; never report them.
            return false;
        }
    }

    /** Reject diagnostic and composite transports before resolving a mailer. */
    private function safeMailer(string $name): bool
    {
        $config = config('mail.driver') ? config('mail') : config('mail.mailers.'.$name);
        if (! is_array($config)) {
            return false;
        }
        $transport = $config['transport'] ?? config('mail.driver');
        if (isset($config['url'])) {
            $url = $config['url'];
            if (! is_string($url) || $url === '' || preg_match('/[\x00-\x20\x7f]/', $url)) {
                return false;
            }
            $parts = parse_url($url);
            if ($parts === false || empty($parts['scheme']) || empty($parts['host']) || isset($parts['fragment'])) {
                return false;
            }
            $effective = (new ConfigurationUrlParser)->parseConfiguration($config);
            $transport = $effective['driver'] ?? null;
            $schemeTransport = ConfigurationUrlParser::getDriverAliases()[$parts['scheme']] ?? $parts['scheme'];
            if ($transport !== $schemeTransport) {
                return false;
            }
        }

        return in_array($transport, ['smtp', 'sendmail', 'ses', 'ses-v2', 'postmark', 'resend', 'mailgun'], true);
    }
}
