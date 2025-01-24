<?php

declare(strict_types=1);


namespace App\Serializer;

use App\Entity\PaymentOrder;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PaymentOrderNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    private const DONE = "payment_order_normalizer.done";

    use NormalizerAwareTrait;

    public function normalize(mixed $object, ?string $format = null, array $context = [])
    {
        $data = $this->normalizer->normalize($object, $format, $context + [self::DONE => true]);

        //Change the amount to a string
        $data['Betrag'] = $this->format($data['Betrag']);
        $data['Unterstützender Betrag'] = $this->format($data['Unterstützender Betrag']);

        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = [])
    {
        if ($context[self::DONE] ?? false) {
            return false;
        }

        return $data instanceof PaymentOrder;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            PaymentOrder::class => false // We check for context
        ];
    }

    private function format(?int $amount): ?string
    {
        if ($amount === null) {
            return null;
        }

        return number_format($amount / 100, 2, ',', '');
    }

}