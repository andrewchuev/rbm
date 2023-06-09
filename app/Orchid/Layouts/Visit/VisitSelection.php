<?php

namespace App\Orchid\Layouts\Visit;

use App\Orchid\Filters\Visit\DriverFilter;
use App\Orchid\Filters\Visit\PlaceFilter;
use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;

class VisitSelection extends Selection
{

    public $template = self::TEMPLATE_LINE;
    /**
     * @return Filter[]
     */
    public function filters(): iterable
    {
        return [
            DriverFilter::class,
            PlaceFilter::class,
        ];
    }
}
