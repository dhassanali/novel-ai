import { chat } from '@/actions/App/Http/Controllers/CharacterChatController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Character, ChatMessage, Novel } from '@/types/novel';
import axios from 'axios';
import { Send, User } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface CharacterChatModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    character: Character | null;
    novel: Novel;
}

export default function CharacterChatModal({
    open,
    onOpenChange,
    character,
    novel,
}: CharacterChatModalProps) {
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [input, setInput] = useState('');
    const [sending, setSending] = useState(false);
    const bottomRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (open) {
            setMessages([]);
            setInput('');
        }
    }, [open, character?.id]);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    const sendMessage = async () => {
        if (!character || !input.trim() || sending) {
            return;
        }

        const userMessage: ChatMessage = { role: 'user', content: input.trim() };
        const newMessages = [...messages, userMessage];
        setMessages(newMessages);
        setInput('');
        setSending(true);

        try {
            const response = await axios.post(
                chat.url({ novel: novel.id, character: character.id }),
                {
                    message: userMessage.content,
                    history: messages,
                },
            );

            setMessages([
                ...newMessages,
                { role: 'character', content: response.data.reply },
            ]);
        } catch {
            setMessages([
                ...newMessages,
                {
                    role: 'character',
                    content: '(Something went wrong. Please try again.)',
                },
            ]);
        } finally {
            setSending(false);
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[85vh] max-w-xl flex-col">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <User className="h-4 w-4" />
                        Talk to {character?.name}
                    </DialogTitle>
                </DialogHeader>

                <div className="flex-1 overflow-y-auto space-y-3 pr-1 min-h-0 py-2" style={{ maxHeight: '50vh' }}>
                    {messages.length === 0 && (
                        <p className="text-center text-sm text-muted-foreground py-8">
                            Start a conversation with {character?.name}. They will respond in character based on their personality and backstory.
                        </p>
                    )}
                    {messages.map((msg, i) => (
                        <div
                            key={i}
                            className={`flex gap-2 ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}
                        >
                            <div
                                className={`max-w-[80%] rounded-lg px-3 py-2 text-sm ${
                                    msg.role === 'user'
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-muted'
                                }`}
                            >
                                {msg.role === 'character' && (
                                    <p className="mb-1 text-xs font-semibold text-muted-foreground">
                                        {character?.name}
                                    </p>
                                )}
                                <p className="whitespace-pre-wrap">{msg.content}</p>
                            </div>
                        </div>
                    ))}
                    {sending && (
                        <div className="flex justify-start">
                            <div className="rounded-lg bg-muted px-3 py-2 text-sm text-muted-foreground">
                                <span className="animate-pulse">{character?.name} is thinking...</span>
                            </div>
                        </div>
                    )}
                    <div ref={bottomRef} />
                </div>

                <div className="flex gap-2 border-t pt-3">
                    <Textarea
                        value={input}
                        onChange={(e) => setInput(e.target.value)}
                        onKeyDown={handleKeyDown}
                        placeholder={`Ask ${character?.name} something... (Enter to send)`}
                        rows={2}
                        className="resize-none"
                        disabled={sending}
                    />
                    <Button
                        size="icon"
                        onClick={sendMessage}
                        disabled={!input.trim() || sending}
                        className="shrink-0 self-end"
                    >
                        <Send className="h-4 w-4" />
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
