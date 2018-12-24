<?php

declare(strict_types=1);

namespace Hateoas\Serializer;

use Hateoas\Model\Embedded;
use JMS\Serializer\Exception\NotAcceptableException;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\SerializationVisitorInterface;

class JsonHalSerializer implements JsonSerializerInterface
{
    public function serializeLinks(array $links, SerializationVisitorInterface $visitor, SerializationContext $context): void
    {
        $serializedLinks = [];
        foreach ($links as $link) {
            $serializedLink = array_merge([
                'href' => $link->getHref(),
            ], $link->getAttributes());

            if (!isset($serializedLinks[$link->getRel()]) && 'curies' !== $link->getRel()) {
                $serializedLinks[$link->getRel()] = $serializedLink;
            } elseif (isset($serializedLinks[$link->getRel()]['href'])) {
                $serializedLinks[$link->getRel()] = [
                    $serializedLinks[$link->getRel()],
                    $serializedLink,
                ];
            } else {
                $serializedLinks[$link->getRel()][] = $serializedLink;
            }
        }

        $visitor->visitProperty(new StaticPropertyMetadata(self::class, '_links', $serializedLinks), $serializedLinks);
    }

    /**
     * @param Embedded [] $embeddeds
     */
    public function serializeEmbeddeds(array $embeddeds, SerializationVisitorInterface $visitor, SerializationContext $context): void
    {
        $serializedEmbeddeds = [];
        $multiple = [];
        $navigator = $context->getNavigator();

        foreach ($embeddeds as $embedded) {
            $context->pushPropertyMetadata($embedded->getMetadata());
            try {
                $data = $navigator->accept($embedded->getData(), $embedded->getType(), $context);

                if (!isset($serializedEmbeddeds[$embedded->getRel()])) {
                    $serializedEmbeddeds[$embedded->getRel()] = $data;
                } elseif (!isset($multiple[$embedded->getRel()])) {
                    $multiple[$embedded->getRel()] = true;

                    $serializedEmbeddeds[$embedded->getRel()] = [$serializedEmbeddeds[$embedded->getRel()], $data];
                } else {
                    $serializedEmbeddeds[$embedded->getRel()][] = $data;
                }
            } catch (NotAcceptableException $e) {
            }

            $context->popPropertyMetadata();
        }

        $visitor->visitProperty(new StaticPropertyMetadata(self::class, '_embedded', $serializedEmbeddeds), $serializedEmbeddeds);
    }
}
