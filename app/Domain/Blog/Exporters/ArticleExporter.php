<?php

declare(strict_types=1);

namespace App\Domain\Blog\Exporters;

use App\Domain\Blog\Repositories\ArticleRepositoryInterface;
use App\Domain\Shared\Exporters\BaseExporter;
use Illuminate\Support\Collection;

/**
 * Article Exporter
 * Exports articles with all fields
 */
class ArticleExporter extends BaseExporter
{
    /**
     * Constructor
     */
    public function __construct(
        protected ArticleRepositoryInterface $articleRepository,
        string $language = 'en',
        array $filters = [],
        array $selectedColumns = []
    ) {
        parent::__construct($language, $filters, $selectedColumns);
    }

    /**
     * Get the collection to export
     */
    public function collection(): Collection
    {
        // Get all articles or filtered articles
        if (! empty($this->filters)) {
            // Use pagination but get all results
            $query = $this->articleRepository->getModel()::query()
                ->with(['author', 'category', 'tags', 'series']);

            // Apply filters
            if (isset($this->filters['status'])) {
                $query->where('status', $this->filters['status']);
            }

            if (isset($this->filters['category_id'])) {
                $query->where('category_id', $this->filters['category_id']);
            }

            if (isset($this->filters['author_id'])) {
                $query->where('author_id', $this->filters['author_id']);
            }

            if (isset($this->filters['series_id'])) {
                $query->where('series_id', $this->filters['series_id']);
            }

            if (isset($this->filters['is_featured'])) {
                $query->where('is_featured', (bool) $this->filters['is_featured']);
            }

            if (isset($this->filters['start_date'])) {
                $query->whereDate('created_at', '>=', $this->filters['start_date']);
            }

            if (isset($this->filters['end_date'])) {
                $query->whereDate('created_at', '<=', $this->filters['end_date']);
            }

            if (isset($this->filters['search'])) {
                $search = $this->filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.ar')) LIKE ?", ["%{$search}%"])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) LIKE ?", ["%{$search}%"]);
                });
            }

            return $query->get();
        }

        return $this->articleRepository->all(['*'], ['author', 'category', 'tags', 'series']);
    }

    /**
     * Map each row
     */
    public function map($article): array
    {
        $row = [];

        foreach ($this->selectedColumns as $column) {
            $row[] = match ($column) {
                'id' => $article->id,
                'title' => $this->formatMultiLanguageField($article->title),
                'slug' => $article->slug,
                'excerpt' => $this->truncate($this->formatMultiLanguageField($article->excerpt), 200),
                'content' => $this->truncate($this->formatMultiLanguageField($article->content), 500),
                'category' => $article->category?->name[$this->language] ?? $article->category?->name['ar'] ?? '',
                'author' => $article->author?->name ?? '',
                'series' => $article->series?->title[$this->language] ?? $article->series?->title['ar'] ?? '',
                'tags' => $article->tags?->pluck('name.'.$this->language)->filter()->implode(', ') ?? '',
                'status' => ucfirst($article->status),
                'is_featured' => $this->formatBoolean($article->is_featured),
                'view_count' => $article->view_count ?? 0,
                'reading_time' => $article->reading_time ?? 0,
                'published_at' => $this->formatDate($article->published_at),
                'created_at' => $this->formatDate($article->created_at),
                'updated_at' => $this->formatDate($article->updated_at),
                default => '',
            };
        }

        return $row;
    }

    /**
     * Get column headers
     */
    public function headings(): array
    {
        $translations = [
            'id' => ['ar' => 'المعرف', 'en' => 'ID', 'fr' => 'ID', 'tr' => 'Kimlik', 'ur' => 'شناخت'],
            'title' => ['ar' => 'العنوان', 'en' => 'Title', 'fr' => 'Titre', 'tr' => 'Başlık', 'ur' => 'عنوان'],
            'slug' => ['ar' => 'الرابط', 'en' => 'Slug', 'fr' => 'Slug', 'tr' => 'Slug', 'ur' => 'سلگ'],
            'excerpt' => ['ar' => 'المقتطف', 'en' => 'Excerpt', 'fr' => 'Extrait', 'tr' => 'Alıntı', 'ur' => 'اقتباس'],
            'content' => ['ar' => 'المحتوى', 'en' => 'Content', 'fr' => 'Contenu', 'tr' => 'İçerik', 'ur' => 'مواد'],
            'category' => ['ar' => 'التصنيف', 'en' => 'Category', 'fr' => 'Catégorie', 'tr' => 'Kategori', 'ur' => 'قسم'],
            'author' => ['ar' => 'الكاتب', 'en' => 'Author', 'fr' => 'Auteur', 'tr' => 'Yazar', 'ur' => 'مصنف'],
            'series' => ['ar' => 'السلسلة', 'en' => 'Series', 'fr' => 'Série', 'tr' => 'Seri', 'ur' => 'سلسلہ'],
            'tags' => ['ar' => 'الوسوم', 'en' => 'Tags', 'fr' => 'Tags', 'tr' => 'Etiketler', 'ur' => 'ٹیگز'],
            'status' => ['ar' => 'الحالة', 'en' => 'Status', 'fr' => 'Statut', 'tr' => 'Durum', 'ur' => 'حالت'],
            'is_featured' => ['ar' => 'مميز', 'en' => 'Featured', 'fr' => 'En vedette', 'tr' => 'Öne Çıkan', 'ur' => 'نمایاں'],
            'view_count' => ['ar' => 'المشاهدات', 'en' => 'Views', 'fr' => 'Vues', 'tr' => 'Görüntüleme', 'ur' => 'مناظر'],
            'reading_time' => ['ar' => 'وقت القراءة', 'en' => 'Reading Time (min)', 'fr' => 'Temps de lecture', 'tr' => 'Okuma Süresi', 'ur' => 'پڑھنے کا وقت'],
            'published_at' => ['ar' => 'تاريخ النشر', 'en' => 'Published At', 'fr' => 'Publié le', 'tr' => 'Yayın Tarihi', 'ur' => 'شائع شدہ'],
            'created_at' => ['ar' => 'تاريخ الإنشاء', 'en' => 'Created At', 'fr' => 'Créé le', 'tr' => 'Oluşturulma', 'ur' => 'تخلیق کردہ'],
            'updated_at' => ['ar' => 'تاريخ التحديث', 'en' => 'Updated At', 'fr' => 'Mis à jour le', 'tr' => 'Güncellenme', 'ur' => 'تازہ کاری'],
        ];

        $headers = [];
        foreach ($this->selectedColumns as $column) {
            $headers[] = $this->getTranslation($translations[$column] ?? ['en' => $column]);
        }

        return $headers;
    }

    /**
     * Get available columns for export
     */
    public function getAvailableColumns(): array
    {
        return [
            'id',
            'title',
            'slug',
            'excerpt',
            'content',
            'category',
            'author',
            'series',
            'tags',
            'status',
            'is_featured',
            'view_count',
            'reading_time',
            'published_at',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Get default columns if none selected
     */
    public function getDefaultColumns(): array
    {
        return [
            'id',
            'title',
            'category',
            'author',
            'status',
            'is_featured',
            'view_count',
            'published_at',
            'created_at',
        ];
    }
}
