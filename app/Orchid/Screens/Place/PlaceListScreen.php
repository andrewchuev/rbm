<?php

namespace App\Orchid\Screens\Place;

use App\Models\Place;
use App\Orchid\Filters\PlaceFilter;
use App\Orchid\Layouts\Place\PlaceFiltersLayout;
use App\Orchid\Layouts\Place\PlaceListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;


class PlaceListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return ['places' => Place::filtersApply([PlaceFilter::class])->filters()->paginate()];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Places List';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make(__('Add'))
                ->icon('plus')
                ->route('platform.places.create'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            PlaceFiltersLayout::class,
            PlaceListLayout::class
        ];
    }
}
