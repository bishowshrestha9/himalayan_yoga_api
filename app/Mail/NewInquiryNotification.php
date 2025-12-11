<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Service;

class NewInquiryNotification extends Mailable
{
    use Queueable, SerializesModels;

    private $inquiryName;
    private $inquiryEmail;
    private $inquiryPhone;
    private $inquiryMessage;
    private $inquiryServiceName;
    private $inquiryReceivedAt;

    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        $this->inquiryName = $data['name'];
        $this->inquiryEmail = $data['email'];
        $this->inquiryPhone = $data['phone'];
        $this->inquiryMessage = $data['message'];
        
        // Get service name if service_id is provided
        if (isset($data['service_id']) && !empty($data['service_id'])) {
            $service = Service::find($data['service_id']);
            $this->inquiryServiceName = $service ? $service->title : null;
        } else {
            $this->inquiryServiceName = null;
        }
        
        // Format the received timestamp
        $this->inquiryReceivedAt = now()->format('F d, Y h:i A');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Inquiry Notification',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.inquiry_notification',
            with: [
                'name' => $this->inquiryName,
                'email' => $this->inquiryEmail,
                'phone' => $this->inquiryPhone,
                'inquiryMessage' => $this->inquiryMessage,
                'service_name' => $this->inquiryServiceName,
                'received_at' => $this->inquiryReceivedAt,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
