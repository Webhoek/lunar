<?php
 
 namespace Lunar\Admin\Filament\Clusters;
 
use Filament\Clusters\Cluster;
 
class Marketplace extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Marketplace';

    protected static ?string $slug = 'marketplace';

    protected static ?string $clusterBreadcrumb = 'marketplace';
    
    public static function navigation(): bool
    {
        return false; // Keep this true if you want the cluster itself in the menu
    }
}