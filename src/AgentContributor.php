<?php

declare(strict_types=1);

namespace NimbusCMS\Analytics;

use Nimbus\Site\HeadContributor;
use Nimbus\Site\PageContext;

/** Injects the configured third-party agent's snippet into every public page. */
final class AgentContributor implements HeadContributor
{
    public function __construct(private Agent $agent)
    {
    }

    public function head(PageContext $page): string
    {
        return $this->agent->snippet();
    }
}
