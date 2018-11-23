<?php

namespace Hateoas\Serializer\Metadata;

use Hateoas\Configuration\Exclusion;
use Hateoas\Configuration\Relation;
use JMS\Serializer\Metadata\VirtualPropertyMetadata;
use JMS\Serializer\TypeParser;
use Symfony\Component\ExpressionLanguage\Expression;

/**
 * @author Adrien Brault <adrien.brault@gmail.com>
 */
class RelationPropertyMetadata extends VirtualPropertyMetadata
{
    const EXPRESSION_REGEX = '/expr\((?P<expression>.+)\)/';

    public function __construct(Exclusion $exclusion = null, Relation $relation = null)
    {
        if (null !== $relation) {
            $this->name = $relation->getName();
            $this->class = get_class($relation);

            if (null !== $relation->getEmbedded()) {
                $this->type = array('name' => 'Hateoas\Model\Embedded', 'params' => []);
            } elseif (null !== $relation->getHref()) {
                $this->type = array('name' => 'Hateoas\Model\Link', 'params' => []);
            }
        }

        if (null === $exclusion) {
            return;
        }

        $this->groups = $exclusion->getGroups();
        $this->sinceVersion = $exclusion->getSinceVersion();
        $this->untilVersion = $exclusion->getUntilVersion();
        $this->maxDepth = $exclusion->getMaxDepth();

        if ($exclusion->getExcludeIf() instanceof Expression) {
            $this->excludeIf = (string)$exclusion->getExcludeIf() ;
        } elseif ($exclusion->getExcludeIf() !== null) {
            $this->excludeIf = $exclusion->getExcludeIf();
        }
    }
}
