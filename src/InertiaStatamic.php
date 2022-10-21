<?php

namespace Hotmeteor\Inertia;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use JsonSerializable;
use Statamic\Entries\Entry;
use Statamic\Facades\Data;
use Statamic\Fields\Value;
use Statamic\Structures\Page;

class InertiaStatamic
{
    /**
     * Return an Inertia response containing the Statamic data.
     *
     * @return \Inertia\Response|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $page = Data::findByRequestUrl($request->url());

        if (($page instanceof Page || $page instanceof Entry) && $page->template() === 'app') {
            return Inertia::render(
                $this->buildComponentPath($page),
                $this->buildProps($page)
            );
        }

        return $next($request);
    }

    /**
     * Build the path for the component based blueprint name.
     *
     * @param $data
     * @return string
     */
    protected function buildComponentPath($data)
    {
        $values = $data->toAugmentedArray();

        return $values['blueprint']->value()->title;
    }

    /**
     * Convert the Statamic object into props.
     *
     * @param $data
     * @return array|Carbon|mixed
     */
    protected function buildProps($data)
    {
        if ($data instanceof Carbon) {
            return $data;
        }

        if ($data instanceof JsonSerializable || $data instanceof Collection) {
            return $this->buildProps($data->jsonSerialize());
        }

        if (is_array($data)) {
            return collect($data)->map(function ($value) {
                return $this->buildProps($value);
            })->all();
        }

        if ($data instanceof Value) {
            return $data->value();
        }

        if (is_object($data) && method_exists($data, 'toAugmentedArray')) {
            return $this->buildProps($data->toAugmentedArray());
        }

        return $data;
    }
}
