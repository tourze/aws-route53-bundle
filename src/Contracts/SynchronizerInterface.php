<?php

declare(strict_types=1);

namespace Tourze\AwsRoute53Bundle\Contracts;

use Tourze\AwsRoute53Bundle\Entity\AwsAccount;
use Tourze\AwsRoute53Bundle\Entity\HostedZone;

interface SynchronizerInterface
{
    /** @return array<string, mixed> */
    public function pullFromRemote(AwsAccount $account, ?HostedZone $zone = null, bool $dryRun = false): array;

    /** @return array<string, mixed> */
    public function pushToRemote(AwsAccount $account, ?HostedZone $zone = null, bool $dryRun = false): array;

    /** @return array<string, mixed> */
    public function bidirectionalSync(AwsAccount $account, ?HostedZone $zone = null, string $mode = 'local_wins', bool $dryRun = false): array;
}
