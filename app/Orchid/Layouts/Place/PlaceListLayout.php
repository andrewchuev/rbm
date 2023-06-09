<?php

namespace App\Orchid\Layouts\Place;

use App\Models\Driver;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use App\Models\Place;

class PlaceListLayout extends Table
{
    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the table.
     *
     * @var string
     */
    protected $target = 'places';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('id','ID'),
            TD::make('name','Название')->filter(Input::make())->sort(),
            TD::make('area','Участок')->sort()->render(fn(Place $place) => $place->area?->name),
            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (Place $place) => DropDown::make()
                    ->icon('options-vertical')
                    ->list([

                        Link::make(__('Изменить'))
                            ->route('platform.places.edit', $place->id)
                            ->icon('pencil'),

                        Button::make(__('Удалить'))
                            ->icon('trash')
                            ->confirm(__('Подтвердите удаление'))
                            ->method('remove', [
                                'id' => $place->id,
                            ]),
                    ]))

        ];
    }
}
