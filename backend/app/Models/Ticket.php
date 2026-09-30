<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'assignee_id', 'title', 'description', 'priority', 'status', 'protocol'])]
class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    /** Prefixo do protocolo. O formato completo e CH-AAAA-NNNNN. */
    public const PROTOCOL_PREFIX = 'CH';

    /** Casas reservadas para a sequencia dentro do ano. */
    private const PROTOCOL_PADDING = 5;

    /**
     * O protocolo nasce no model, e nao no TicketService, porque a factory e o
     * seeder criam chamados por fora do service. Gerar aqui e o unico jeito de
     * garantir a coluna em qualquer caminho de insercao.
     */
    protected static function booted(): void
    {
        static::creating(function (self $ticket) {
            $ticket->protocol ??= self::nextProtocol();
        });
    }

    private static function nextProtocol(): string
    {
        $year = (int) now()->format('Y');
        $prefix = self::PROTOCOL_PREFIX.'-'.$year.'-';

        // withTrashed e obrigatorio: o chamado removido por soft delete sai da
        // consulta, mas continua ocupando o protocolo no indice unico. Sem
        // isso, abrir um chamado depois de excluir outro reusa o numero e a
        // criacao falha por violacao de unicidade.
        $last = self::withTrashed()
            ->where('protocol', 'like', $prefix.'%')
            ->orderByDesc('protocol')
            ->value('protocol');

        $sequence = $last === null
            ? 1
            : ((int) substr($last, strlen($prefix))) + 1;

        return $prefix.str_pad((string) $sequence, self::PROTOCOL_PADDING, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        $column = $viewer->visibleTicketColumn();

        return $column === null
            ? $query
            : $query->where($column, $viewer->getKey());
    }
}
