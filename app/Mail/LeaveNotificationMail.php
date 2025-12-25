<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $leave;
    public $recipient;
    public $employee;
    public $template;
    public $subjectStr;
    public $comments;
    public $manager;
    public $approver;

    /**
     * Create a new message instance.
     */
    public function __construct($leave, $recipient, $template, $subjectStr, $comments = null, $approver = null)
    {
        $this->leave = $leave;
        $this->recipient = $recipient;
        $this->template = $template;
        $this->subjectStr = $subjectStr;
        $this->comments = $comments;
        $this->approver = $approver;

        // Get the Employee model (not the User)
        $this->employee = $leave->employee->employee ?? null;

        if ($this->employee) {
            // Alias matricule for templates
            $this->employee->matricule = $this->employee->employee_id;
        }

        // Add helper fields the templates expect
        $this->leave->type = $leave->leaveType->name ?? 'N/A';
        $this->leave->days = $leave->total_days ?? 0;

        // Add manager variable if expected by template
        $this->manager = $recipient;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectStr,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.leaves.' . $this->template,
            with: [
                'leave' => $this->leave,
                'recipient' => $this->recipient,
                'employee' => $this->employee,
                'manager' => $this->manager,
                'comments' => $this->comments,
                'approver' => $this->approver,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
