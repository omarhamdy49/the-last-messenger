<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Infrastructure\Llm\ChatClientInterface;
use App\Domain\Quran\Infrastructure\Llm\ChatResult;
use App\Domain\Quran\Infrastructure\Mcp\QuranMcpClientInterface;
use App\Domain\Quran\Services\QuranAiInquiryService;
use Tests\TestCase;

/**
 * The quran.ai MCP grounding rules mandate a trailing
 * `_Grounded with quran.ai: ..._` provenance line, which contradicts our own
 * "no source footers" rule — and the models obey the MCP rules. It is stripped
 * in code so it never reaches the user, the DB, or the 60-day answer cache.
 */
final class GroundingFooterStripTest extends TestCase
{
    public function test_it_strips_the_grounded_with_footer(): void
    {
        $answer = $this->answerReturning(
            "سورة الكوثر [[108:1]] معناها الخير الكثير.\n\n"
            .'_Grounded with quran.ai: fetch_quran(108:1, ar-simple-clean), fetch_tafsir(108:1, en-maarif-ul-quran)_'
        );

        $this->assertSame('سورة الكوثر [[108:1]] معناها الخير الكثير.', $answer);
    }

    public function test_it_strips_a_multi_reference_footer(): void
    {
        $answer = $this->answerReturning(
            "الجواب هنا [[2:30]].\n_Grounded with quran.ai: fetch_quran(2:30-39, 2:49-74, 2:243-252, 2:258-260)_"
        );

        $this->assertSame('الجواب هنا [[2:30]].', $answer);
    }

    public function test_it_keeps_the_unverified_content_warning(): void
    {
        // This variant warns the reader the content is unverified — stripping it
        // would hide a safety signal, so it must survive.
        $body = "الجواب [[2:255]].\n\n_Note: this response was not grounded with canonical text from quran.ai — accuracy unverified_";

        $this->assertSame($body, $this->answerReturning($body));
    }

    public function test_it_leaves_an_answer_without_a_footer_untouched(): void
    {
        $body = 'سورة البقرة [[2:153]] — الصبر حبس النفس.';

        $this->assertSame($body, $this->answerReturning($body));
    }

    private function answerReturning(string $content): string
    {
        $service = new QuranAiInquiryService(
            new class($content) implements ChatClientInterface
            {
                public function __construct(private readonly string $content) {}

                public function chat(array $messages, array $tools = [], string $toolChoice = 'auto'): ChatResult
                {
                    return new ChatResult(['role' => 'assistant', 'content' => $this->content], [], 'deepseek');
                }

                public function streamChat(array $messages, array $tools, string $toolChoice, callable $onContentDelta): ChatResult
                {
                    return $this->chat($messages, $tools, $toolChoice);
                }
            },
            new class implements QuranMcpClientInterface
            {
                public function grounding(): array
                {
                    return ['rules' => 'Ground every claim.', 'nonce' => null];
                }

                public function toolDefinitions(): array
                {
                    return [];
                }

                public function callTool(string $name, array $arguments): string
                {
                    return '{}';
                }
            },
            'deepseek',
        );

        return $service->answer('ما معنى الكوثر؟', 'ar')->answer;
    }
}
