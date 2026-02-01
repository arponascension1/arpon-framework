<?php

namespace Arpon\Pagination;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use ArrayIterator;
use Traversable;

class Paginator implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    protected $items;
    protected $total;
    protected $perPage;
    protected $currentPage;
    protected $lastPage;
    protected $path;
    protected $query = [];
    protected $fragment;

    public function __construct($items, int $total, int $perPage, int $currentPage, array $options = [])
    {
        $this->items = $items;
        $this->total = $total;
        $this->perPage = $perPage;
        $this->currentPage = $currentPage;
        $this->lastPage = (int) ceil($total / $perPage);
        $this->path = $options['path'] ?? '/';
        $this->query = $options['query'] ?? [];
        $this->fragment = $options['fragment'] ?? null;
    }

    public function items()
    {
        return $this->items;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function lastPage(): int
    {
        return $this->lastPage;
    }

    public function firstItem(): ?int
    {
        return count($this->items) > 0 ? ($this->currentPage - 1) * $this->perPage + 1 : null;
    }

    public function lastItem(): ?int
    {
        return count($this->items) > 0 ? $this->firstItem() + count($this->items) - 1 : null;
    }

    public function hasPages(): bool
    {
        return $this->lastPage > 1;
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    public function onFirstPage(): bool
    {
        return $this->currentPage <= 1;
    }

    public function onLastPage(): bool
    {
        return $this->currentPage >= $this->lastPage;
    }

    public function isEmpty(): bool
    {
        return count($this->items) === 0;
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        if (is_array($this->items)) {
            return new ArrayIterator($this->items);
        }
        
        // If items is a Collection, it's already iterable
        if ($this->items instanceof Traversable) {
            return $this->items;
        }
        
        return new ArrayIterator([]);
    }

    public function url($page): string
    {
        if ($page <= 0) {
            $page = 1;
        }

        $parameters = ['page' => $page] + $this->query;
        $path = $this->path . '?' . http_build_query($parameters);

        return $this->fragment ? $path . '#' . $this->fragment : $path;
    }

    public function previousPageUrl(): ?string
    {
        return $this->currentPage > 1 ? $this->url($this->currentPage - 1) : null;
    }

    public function nextPageUrl(): ?string
    {
        return $this->hasMorePages() ? $this->url($this->currentPage + 1) : null;
    }

    public function links($view = null): string
    {
        if (!$this->hasPages()) {
            return '';
        }

        if ($view) {
            return view($view, ['paginator' => $this])->render();
        }

        return $this->renderPagination();
    }

    protected function renderPagination(): string
    {
        $html = '<nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">';
        $html .= '<div class="flex justify-between flex-1 sm:hidden">';
        
        // Mobile Previous
        if ($this->onFirstPage()) {
            $html .= '<span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md">Previous</span>';
        } else {
            $html .= '<a href="' . $this->previousPageUrl() . '" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">Previous</a>';
        }

        // Mobile Next
        if ($this->hasMorePages()) {
            $html .= '<a href="' . $this->nextPageUrl() . '" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">Next</a>';
        } else {
            $html .= '<span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md">Next</span>';
        }

        $html .= '</div>';
        $html .= '<div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">';
        $html .= '<div><p class="text-sm text-gray-700 leading-5">';
        $html .= 'Showing <span class="font-medium">' . $this->firstItem() . '</span>';
        $html .= ' to <span class="font-medium">' . $this->lastItem() . '</span>';
        $html .= ' of <span class="font-medium">' . $this->total() . '</span> results';
        $html .= '</p></div>';
        
        // Desktop pagination
        $html .= '<div><span class="relative z-0 inline-flex shadow-sm rounded-md">';
        $html .= $this->renderPageLinks();
        $html .= '</span></div>';
        
        $html .= '</div>';
        $html .= '</nav>';

        return $html;
    }

    protected function renderPageLinks(): string
    {
        $html = '';
        $window = 2;

        // Previous button
        if ($this->onFirstPage()) {
            $html .= '<span aria-disabled="true" aria-label="Previous"><span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-l-md leading-5" aria-hidden="true"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg></span></span>';
        } else {
            $html .= '<a href="' . $this->previousPageUrl() . '" rel="prev" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md leading-5 hover:text-gray-400 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150" aria-label="Previous"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg></a>';
        }

        // First page
        if ($this->currentPage > $window + 2) {
            $html .= $this->getPageLink(1);
            if ($this->currentPage > $window + 3) {
                $html .= '<span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 cursor-default leading-5">...</span>';
            }
        }

        // Page numbers
        for ($page = max(1, $this->currentPage - $window); $page <= min($this->lastPage, $this->currentPage + $window); $page++) {
            $html .= $this->getPageLink($page);
        }

        // Last page
        if ($this->currentPage < $this->lastPage - $window - 1) {
            if ($this->currentPage < $this->lastPage - $window - 2) {
                $html .= '<span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 cursor-default leading-5">...</span>';
            }
            $html .= $this->getPageLink($this->lastPage);
        }

        // Next button
        if ($this->hasMorePages()) {
            $html .= '<a href="' . $this->nextPageUrl() . '" rel="next" class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md leading-5 hover:text-gray-400 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150" aria-label="Next"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg></a>';
        } else {
            $html .= '<span aria-disabled="true" aria-label="Next"><span class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-r-md leading-5" aria-hidden="true"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg></span></span>';
        }

        return $html;
    }

    protected function getPageLink($page): string
    {
        if ($page == $this->currentPage) {
            return '<span aria-current="page"><span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-white bg-blue-600 border border-blue-600 cursor-default leading-5">' . $page . '</span></span>';
        }

        return '<a href="' . $this->url($page) . '" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-500 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150" aria-label="Go to page ' . $page . '">' . $page . '</a>';
    }

    public function offsetExists($offset): bool
    {
        if (in_array($offset, ['current_page', 'data', 'first_page_url', 'from', 'last_page', 'last_page_url', 'next_page_url', 'path', 'per_page', 'prev_page_url', 'to', 'total'])) {
            return true;
        }
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        switch ($offset) {
            case 'current_page':
                return $this->currentPage();
            case 'data':
                return $this->items;
            case 'first_page_url':
                return $this->url(1);
            case 'from':
                return $this->firstItem();
            case 'last_page':
                return $this->lastPage();
            case 'last_page_url':
                return $this->url($this->lastPage());
            case 'next_page_url':
                return $this->nextPageUrl();
            case 'path':
                return $this->path;
            case 'per_page':
                return $this->perPage();
            case 'prev_page_url':
                return $this->previousPageUrl();
            case 'to':
                return $this->lastItem();
            case 'total':
                return $this->total();
        }
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    public function jsonSerialize(): array
    {
        return [
            'current_page' => $this->currentPage(),
            'data' => $this->items,
            'first_page_url' => $this->url(1),
            'from' => $this->firstItem(),
            'last_page' => $this->lastPage(),
            'last_page_url' => $this->url($this->lastPage()),
            'next_page_url' => $this->nextPageUrl(),
            'path' => $this->path,
            'per_page' => $this->perPage(),
            'prev_page_url' => $this->previousPageUrl(),
            'to' => $this->lastItem(),
            'total' => $this->total(),
        ];
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
