<?php

namespace App\Services;

use App\Models\Product;

/**
 * Builds "Order via WhatsApp" links from the admin-configured number and
 * message template. Nothing here is hard-coded.
 */
class WhatsAppOrderService
{
    public function isEnabled(): bool
    {
        return (bool) setting('whatsapp_enabled') && $this->phoneNumber() !== '';
    }

    public function isAvailableFor(Product $product): bool
    {
        return $this->isEnabled()
            && $product->whatsapp_enabled
            && ! $product->is_free
            && $product->isPublished();
    }

    /**
     * International number in digits only, e.g. 923001234567.
     */
    public function phoneNumber(): string
    {
        $code = preg_replace('/\D+/', '', (string) setting('whatsapp_country_code', ''));
        $number = ltrim(preg_replace('/\D+/', '', (string) setting('whatsapp_number', '')), '0');

        if ($number === '') {
            return '';
        }

        if ($code !== '' && ! str_starts_with($number, $code)) {
            $number = $code.$number;
        }

        return $number;
    }

    public function message(Product $product): string
    {
        $template = (string) setting('whatsapp_message_template');

        return strtr($template, [
            '{product_name}' => $product->name,
            '{product_id}' => (string) $product->getKey(),
            '{price}' => money($product->finalPrice()),
            '{regular_price}' => money($product->price),
            '{product_url}' => $product->url(),
            '{category}' => (string) $product->category?->name,
            '{publisher}' => $product->publisherName(),
            '{site_name}' => (string) setting('site_name'),
        ]);
    }

    public function url(Product $product): string
    {
        return 'https://wa.me/'.$this->phoneNumber().'?text='.rawurlencode($this->message($product));
    }

    /**
     * Generic chat link (no product) for the contact page / footer.
     */
    public function chatUrl(?string $text = null): ?string
    {
        $phone = $this->phoneNumber();

        if ($phone === '') {
            return null;
        }

        return 'https://wa.me/'.$phone.($text ? '?text='.rawurlencode($text) : '');
    }
}
