<?php

class Pagination {
    private $totalItems;
    private $itemsPerPage;
    private $currentPage;
    private $totalPages;
    private $urlPattern;
    private $maxPagesToShow = 5;

    public function __construct($totalItems, $itemsPerPage, $currentPage, $urlPattern) {
        $this->totalItems = $totalItems;
        $this->itemsPerPage = $itemsPerPage;
        $this->currentPage = $currentPage;
        $this->urlPattern = $urlPattern;
        $this->totalPages = ceil($totalItems / $itemsPerPage);
    }

    public function getCurrentPage() {
        return $this->currentPage;
    }

    public function getTotalPages() {
        return $this->totalPages;
    }

    public function getOffset() {
        return ($this->currentPage - 1) * $this->itemsPerPage;
    }

    public function getLimit() {
        return $this->itemsPerPage;
    }

    public function getPages() {
        $pages = [];
        $startPage = max(1, $this->currentPage - floor($this->maxPagesToShow / 2));
        $endPage = min($this->totalPages, $startPage + $this->maxPagesToShow - 1);

        if ($endPage - $startPage + 1 < $this->maxPagesToShow) {
            $startPage = max(1, $endPage - $this->maxPagesToShow + 1);
        }

        for ($i = $startPage; $i <= $endPage; $i++) {
            $pages[] = [
                'number' => $i,
                'url' => str_replace('{page}', $i, $this->urlPattern),
                'isCurrent' => $i == $this->currentPage
            ];
        }

        return $pages;
    }

    public function getPrevUrl() {
        if ($this->currentPage > 1) {
            return str_replace('{page}', $this->currentPage - 1, $this->urlPattern);
        }
        return null;
    }

    public function getNextUrl() {
        if ($this->currentPage < $this->totalPages) {
            return str_replace('{page}', $this->currentPage + 1, $this->urlPattern);
        }
        return null;
    }

    public function render() {
        if ($this->totalPages <= 1) {
            return '';
        }

        $html = '<nav aria-label="Page navigation"><ul class="pagination">';
        
        // Bouton précédent
        if ($this->getPrevUrl()) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $this->getPrevUrl() . '">&laquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
        }

        // Pages
        foreach ($this->getPages() as $page) {
            if ($page['isCurrent']) {
                $html .= '<li class="page-item active"><span class="page-link">' . $page['number'] . '</span></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="' . $page['url'] . '">' . $page['number'] . '</a></li>';
            }
        }

        // Bouton suivant
        if ($this->getNextUrl()) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $this->getNextUrl() . '">&raquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
        }

        $html .= '</ul></nav>';
        return $html;
    }
} 