import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useBrainstorm } from '@/hooks/useBrainstorm';
import { Novel } from '@/types/novel';
import { Copy, Lightbulb, ThumbsDown, ThumbsUp, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface BrainstormTabProps {
    novel: Novel;
}

export default function BrainstormTab({ novel }: BrainstormTabProps) {
    const [category, setCategory] = useState('Plot Points');
    const [context, setContext] = useState('');
    const {
        generating,
        suggestions,
        keepers,
        generateIdeas,
        addKeeper,
        removeKeeper,
        removeSuggestion,
        clearKeepers,
    } = useBrainstorm({
        novel,
        onError: (error) => console.error(error),
    });

    const handleGenerate = () => {
        generateIdeas(category, context);
    };

    const handleCopyKeepers = () => {
        navigator.clipboard.writeText(keepers.join('\n'));
    };

    return (
        <div className="flex h-full flex-col gap-4 p-2">
            <div className="space-y-2">
                <Label>Category</Label>
                <Select value={category} onValueChange={setCategory}>
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="Plot Points">Plot Points</SelectItem>
                        <SelectItem value="Characters">Characters</SelectItem>
                        <SelectItem value="World Building">
                            World Building
                        </SelectItem>
                        <SelectItem value="Names">Names</SelectItem>
                        <SelectItem value="Dialogue">Dialogue</SelectItem>
                        <SelectItem value="Twists">Twists</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div className="space-y-2">
                <Label>Context (Optional)</Label>
                <Textarea
                    placeholder="E.g., A sci-fi story about a lost astronaut..."
                    value={context}
                    onChange={(e) => setContext(e.target.value)}
                    className="h-20 resize-none"
                />
            </div>
            <Button
                onClick={handleGenerate}
                disabled={generating}
                className="w-full"
            >
                {generating ? 'Generating...' : 'Brainstorm'}
                <Lightbulb className="ml-2 h-4 w-4" />
            </Button>

            <div className="flex-1 space-y-4 overflow-y-auto">
                {suggestions.length > 0 && (
                    <div className="space-y-2">
                        <h3 className="text-sm font-semibold text-muted-foreground">
                            Suggestions
                        </h3>
                        <div className="space-y-2">
                            {suggestions.map((suggestion, index) => (
                                <div
                                    key={index}
                                    className="flex items-start justify-between gap-2 rounded-md border p-2 text-sm"
                                >
                                    <span>{suggestion}</span>
                                    <div className="flex shrink-0 gap-1">
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            className="h-6 w-6 text-green-500 hover:text-green-600"
                                            onClick={() => {
                                                addKeeper(suggestion);
                                                removeSuggestion(suggestion);
                                            }}
                                        >
                                            <ThumbsUp className="h-3 w-3" />
                                        </Button>
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            className="h-6 w-6 text-muted-foreground hover:text-destructive"
                                            onClick={() =>
                                                removeSuggestion(suggestion)
                                            }
                                        >
                                            <ThumbsDown className="h-3 w-3" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {keepers.length > 0 && (
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <h3 className="text-sm font-semibold text-muted-foreground">
                                Keepers
                            </h3>
                            <div className="flex gap-1">
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    className="h-6 w-6"
                                    onClick={handleCopyKeepers}
                                    title="Copy to Clipboard"
                                >
                                    <Copy className="h-3 w-3" />
                                </Button>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    className="h-6 w-6 text-destructive"
                                    onClick={clearKeepers}
                                    title="Clear All"
                                >
                                    <Trash2 className="h-3 w-3" />
                                </Button>
                            </div>
                        </div>
                        <div className="space-y-2">
                            {keepers.map((keeper, index) => (
                                <div
                                    key={index}
                                    className="flex items-start justify-between gap-2 rounded-md bg-muted/50 p-2 text-sm"
                                >
                                    <span>{keeper}</span>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        className="h-6 w-6 shrink-0 text-muted-foreground hover:text-destructive"
                                        onClick={() => removeKeeper(keeper)}
                                    >
                                        <Trash2 className="h-3 w-3" />
                                    </Button>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
