<?php

declare(strict_types=1);

namespace App\Domain\Blog\Exporters;

use App\Domain\Shared\Exporters\BaseExporter;
use App\Models\Article;
use Illuminate\Support\Collection;

/**
 * Article Exporter
 *
 * Exports article records with their localized titles and metadata.
 * Uses the article_locale join to resolve the requested language.
 */
class ArticleExporter extends BaseExporter
{
    public function collection(): Collection
    {
        $query = Article::query()
            ->select(['id', 'writer_id', 'category_id', 'status', 'views', 'read_time', 'is_featured', 'is_pinned', 'hijri_date', 'created_at'])
            ->with([
                'writer:id,name',
                'category:id,slug',
                'category.translations:id,category_id,locale,name',
                'locales:id,article_id,locale,title,slug,is_ready',
            ]);

        if (isset($this->filters['status'])) {
            $status = $this->filters['status'];
            if ($status === 'published') {
                $query->whereHas('locales', fn ($q) => $q->where('is_ready', true));
            } elseif ($status === 'draft') {
                $query->whereDoesntHave('locales', fn ($q) => $q->where('is_ready', true));
            }
        }

        if (isset($this->filters['category_id'])) {
            $categoryId = (int) $this->filters['category_id'];
            $query->where(function ($q) use ($categoryId): void {
                $q->where('category_id', $categoryId)
                    ->orWhereHas('categories', fn ($sub) => $sub->where('categories.id', $categoryId));
            });
        }

        if (isset($this->filters['is_featured'])) {
            $query->where('is_featured', (bool) $this->filters['is_featured']);
        }

        if (isset($this->filters['date_from'])) {
            $query->where('created_at', '>=', $this->filters['date_from']);
        }

        if (isset($this->filters['date_to'])) {
            $query->where('created_at', '<=', $this->filters['date_to']);
        }

        if (isset($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->whereHas('locales', function ($q) use ($search): void {
                $q->where('title', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function map($article): array
    {
        $locale = $article->locales->firstWhere('locale', $this->language)
            ?? $article->locales->firstWhere('locale', 'ar');

        $row = [];

        foreach ($this->selectedColumns as $column) {
            $row[] = match ($column) {
                'id' => $article->id,
                'title' => $locale?->title ?? '',
                'slug' => $locale?->slug ?? '',
                'status' => $locale?->is_ready ? 'published' : 'draft',
                // categories.name was moved to category_translations, so the
                // old attribute read resolved to null on every row.
                'category' => $article->category?->getNameInLanguage($this->language)
                    ?? $article->category?->getNameInLanguage('ar')
                    ?? '',
                'author' => $article->writer?->name ?? '',
                'views' => $article->views ?? 0,
                'read_time' => $article->read_time ?? 0,
                'is_featured' => $this->formatBoolean($article->is_featured),
                'is_pinned' => $this->formatBoolean($article->is_pinned),
                'hijri_date' => $article->hijri_date ?? '',
                'created_at' => $this->formatDate($article->created_at),
                default => '',
            };
        }

        return $row;
    }

    public function headings(): array
    {
        $translations = [
            'id' => ['ar' => 'المعرف',         'en' => 'ID',          'fr' => 'ID',              'tr' => 'Kimlik',           'ur' => 'شناخت'],
            'title' => ['ar' => 'العنوان',        'en' => 'Title',       'fr' => 'Titre',            'tr' => 'Başlık',           'ur' => 'عنوان'],
            'slug' => ['ar' => 'الرابط',         'en' => 'Slug',        'fr' => 'Slug',             'tr' => 'Slug',             'ur' => 'سلگ'],
            'status' => ['ar' => 'الحالة',         'en' => 'Status',      'fr' => 'Statut',           'tr' => 'Durum',            'ur' => 'حالت'],
            'category' => ['ar' => 'التصنيف',        'en' => 'Category',    'fr' => 'Catégorie',        'tr' => 'Kategori',         'ur' => 'زمرہ'],
            'author' => ['ar' => 'الكاتب',         'en' => 'Author',      'fr' => 'Auteur',           'tr' => 'Yazar',            'ur' => 'مصنف'],
            'views' => ['ar' => 'المشاهدات',      'en' => 'Views',       'fr' => 'Vues',             'tr' => 'Görüntüleme',      'ur' => 'مشاہدات'],
            'read_time' => ['ar' => 'وقت القراءة',   'en' => 'Read Time',   'fr' => 'Temps de lecture', 'tr' => 'Okuma Süresi',     'ur' => 'پڑھنے کا وقت'],
            'is_featured' => ['ar' => 'مميز',           'en' => 'Featured',    'fr' => 'En vedette',       'tr' => 'Öne Çıkan',        'ur' => 'نمایاں'],
            'is_pinned' => ['ar' => 'مثبت',           'en' => 'Pinned',      'fr' => 'Épinglé',          'tr' => 'Sabitlenmiş',      'ur' => 'پن شدہ'],
            'hijri_date' => ['ar' => 'التاريخ الهجري', 'en' => 'Hijri Date',  'fr' => 'Date hégirien',    'tr' => 'Hicri Tarih',      'ur' => 'ہجری تاریخ'],
            'created_at' => ['ar' => 'تاريخ الإنشاء', 'en' => 'Created At',  'fr' => 'Créé le',          'tr' => 'Oluşturulma',      'ur' => 'تخلیق کردہ'],
        ];

        $headers = [];
        foreach ($this->selectedColumns as $column) {
            $headers[] = $this->getTranslation($translations[$column] ?? ['en' => $column]);
        }

        return $headers;
    }

    public function getAvailableColumns(): array
    {
        return [
            'id',
            'title',
            'slug',
            'status',
            'category',
            'author',
            'views',
            'read_time',
            'is_featured',
            'is_pinned',
            'hijri_date',
            'created_at',
        ];
    }

    public function getDefaultColumns(): array
    {
        return [
            'title',
            'status',
            'category',
            'author',
            'views',
            'created_at',
        ];
    }
}
