<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EnviarEmailNotificacao implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly string $userId,
        private readonly string $assunto,
        private readonly string $mensagem,
    ) {}

    public function handle(): void
    {
        $usuario = User::find($this->userId);
        if (!$usuario) return;

        $assunto  = $this->assunto;
        $mensagem = $this->mensagem;

        Mail::send([], [], function ($mail) use ($usuario, $assunto, $mensagem) {
            $mail->to($usuario->email, $usuario->nome)
                ->subject($assunto)
                ->html("<p>{$mensagem}</p>");
        });
    }
}
