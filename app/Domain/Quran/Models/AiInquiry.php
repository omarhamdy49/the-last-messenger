<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A registered user's "Ask Quran" AI question and its grounded answer.
 *
 * Doubles as: user history, the cross-user answer cache source (by
 * `question_hash` + `locale`), the cost ledger, and the admin moderation record.
 *
 * @property int $id
 * @property int $user_id
 * @property string $locale
 * @property string $question
 * @property string $normalized_question
 * @property string $question_hash
 * @property string $answer
 * @property array|null $citations
 * @property string $source 'llm' | 'cache'
 * @property string|null $model
 * @property int $input_tokens
 * @property int $output_tokens
 * @property int $cached_tokens
 * @property string $cost_usd
 * @property int $tool_calls
 * @property array|null $geo
 * @property int|null $published_faq_id
 * @property Carbon|null $answer_edited_at
 * @property int|null $answer_edited_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class AiInquiry extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ai_inquiries';

    public const SOURCE_LLM = 'llm';

    public const SOURCE_CACHE = 'cache';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'locale',
        'question',
        'normalized_question',
        'question_hash',
        'answer',
        'citations',
        'source',
        'model',
        'input_tokens',
        'output_tokens',
        'cached_tokens',
        'cost_usd',
        'tool_calls',
        'geo',
        'published_faq_id',
        'answer_edited_at',
        'answer_edited_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'citations' => 'array',
        'geo' => 'array',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cached_tokens' => 'integer',
        'cost_usd' => 'decimal:6',
        'tool_calls' => 'integer',
        'published_faq_id' => 'integer',
        'answer_edited_at' => 'datetime',
        'answer_edited_by' => 'integer',
    ];

    // User is referenced by string class-name (not ::class) on purpose: the
    // DomainQuran layer may only depend on Shared, so importing App\Models\User
    // would cross a module boundary. The relation still works at runtime.

    /**
     * The user who asked the question.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    /**
     * The admin who last edited the stored answer (if any).
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'answer_edited_by');
    }

    public function isPublished(): bool
    {
        return $this->published_faq_id !== null;
    }
}
