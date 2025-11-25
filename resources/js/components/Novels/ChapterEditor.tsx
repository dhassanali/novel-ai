import Editor from '@/components/Editor';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { Chapter } from '@/types/novel';
import { Check, Loader2, PanelLeft, Pencil } from 'lucide-react';
import { useState } from 'react';

interface ChapterEditorProps {
    activeChapter: Chapter | null;
    content: string;
    saveStatus: 'saved' | 'saving' | 'unsaved';
    isBusy: boolean;
    onChange: (content: string) => void;
    onSave: () => void;
    onEditTitle: () => void;
    onToggleSidebar: () => void;
    showSidebar: boolean;
    aiHandlers: {
        generating: boolean;
        analysisOpen: boolean;
        setAnalysisOpen: (open: boolean) => void;
        analysisResult: string;
        handleGenerate: (prompt: string, webSearchEnabled: boolean) => void;
        handleAnalyze: (text: string) => void;
        handleSuggest: (text: string) => void;
        handleRewrite: (selection: string, instruction: string) => void;
        handleExpand: (selection: string) => void;
    };
}

export default function ChapterEditor({
    activeChapter,
    content,
    saveStatus,
    isBusy,
    onChange,
    onSave,
    onEditTitle,
    onToggleSidebar,
    showSidebar,
    aiHandlers,
}: ChapterEditorProps) {
    const [generateOpen, setGenerateOpen] = useState(false);
    const [prompt, setPrompt] = useState('');
    const [webSearchEnabled, setWebSearchEnabled] = useState(false);
    const [rewriteOpen, setRewriteOpen] = useState(false);
    const [rewriteSelection, setRewriteSelection] = useState('');
    const [rewriteInstruction, setRewriteInstruction] = useState('');
    const [expandOpen, setExpandOpen] = useState(false);
    const [expandSelection, setExpandSelection] = useState('');

    const handleRewriteRequest = (text: string) => {
        setRewriteSelection(text);
        setRewriteOpen(true);
    };

    const handleExpandRequest = (text: string) => {
        setExpandSelection(text);
        setExpandOpen(true);
    };

    const onGenerate = () => {
        aiHandlers.handleGenerate(prompt, webSearchEnabled);
        setGenerateOpen(false);
        setPrompt('');
    };

    const onRewrite = () => {
        aiHandlers.handleRewrite(rewriteSelection, rewriteInstruction);
        setRewriteOpen(false);
        setRewriteInstruction('');
    };

    const onExpand = () => {
        aiHandlers.handleExpand(expandSelection);
        setExpandOpen(false);
    };

    if (!activeChapter) {
        return (
            <div className="flex flex-1 items-center justify-center bg-muted/5 text-muted-foreground">
                Select or create a chapter to start writing.
            </div>
        );
    }

    return (
        <div className="flex flex-1 flex-col">
            <div className="flex items-center justify-between border-b bg-background p-4">
                <div className="flex items-center gap-2">
                    <Button
                        size="icon"
                        variant="ghost"
                        onClick={onToggleSidebar}
                        title={showSidebar ? 'Hide Sidebar' : 'Show Sidebar'}
                    >
                        <PanelLeft className="h-4 w-4" />
                    </Button>
                    <h1 className="text-xl font-bold">{activeChapter.title}</h1>
                    <Button
                        size="icon"
                        variant="ghost"
                        className="h-6 w-6 shrink-0"
                        onClick={onEditTitle}
                    >
                        <Pencil className="h-3 w-3" />
                    </Button>
                </div>
                <div className="flex items-center gap-2">
                    {/* Word Count Display */}
                    <span className="text-sm text-muted-foreground">
                        {activeChapter.word_count.toLocaleString()} words
                    </span>
                    <Separator orientation="vertical" className="h-6" />
                    {saveStatus === 'saving' && (
                        <span className="flex items-center text-xs text-muted-foreground">
                            <Loader2 className="mr-1 h-3 w-3 animate-spin" />
                            Saving...
                        </span>
                    )}
                    {saveStatus === 'saved' && (
                        <span className="flex items-center text-xs text-muted-foreground">
                            <Check className="mr-1 h-3 w-3" />
                            Saved
                        </span>
                    )}
                    {saveStatus === 'unsaved' && (
                        <span className="text-xs text-muted-foreground">
                            Unsaved changes
                        </span>
                    )}
                    <Button
                        variant="outline"
                        onClick={onSave}
                        disabled={saveStatus === 'saving'}
                    >
                        Save
                    </Button>
                    <Dialog open={generateOpen} onOpenChange={setGenerateOpen}>
                        <DialogTrigger asChild>
                            <Button>Generate AI</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Generate Content</DialogTitle>
                            </DialogHeader>
                            <div className="space-y-4">
                                <div>
                                    <Label htmlFor="prompt">Prompt</Label>
                                    <Input
                                        id="prompt"
                                        value={prompt}
                                        onChange={(e) =>
                                            setPrompt(e.target.value)
                                        }
                                        placeholder="Describe what happens next..."
                                    />
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Switch
                                        id="web-search"
                                        checked={webSearchEnabled}
                                        onCheckedChange={setWebSearchEnabled}
                                    />
                                    <Label htmlFor="web-search">
                                        Enable Web Search
                                    </Label>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button
                                    onClick={onGenerate}
                                    disabled={aiHandlers.generating}
                                >
                                    {aiHandlers.generating
                                        ? 'Generating...'
                                        : 'Generate'}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Dialog
                        open={aiHandlers.analysisOpen}
                        onOpenChange={aiHandlers.setAnalysisOpen}
                    >
                        <DialogContent className="max-w-2xl">
                            <DialogHeader>
                                <DialogTitle>AI Result</DialogTitle>
                            </DialogHeader>
                            <div className="max-h-[60vh] overflow-y-auto whitespace-pre-wrap">
                                {aiHandlers.analysisResult}
                            </div>
                            <DialogFooter>
                                <Button
                                    onClick={() =>
                                        aiHandlers.setAnalysisOpen(false)
                                    }
                                >
                                    Close
                                </Button>
                                <Button
                                    variant="secondary"
                                    onClick={() => {
                                        navigator.clipboard.writeText(
                                            aiHandlers.analysisResult.replace(
                                                /^(Rewritten Text:|Expanded Scene:)\n\n/,
                                                '',
                                            ),
                                        );
                                        aiHandlers.setAnalysisOpen(false);
                                    }}
                                >
                                    Copy
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Dialog open={rewriteOpen} onOpenChange={setRewriteOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Rewrite Selection</DialogTitle>
                            </DialogHeader>
                            <div className="space-y-4">
                                <div>
                                    <Label htmlFor="instruction">
                                        Instructions
                                    </Label>
                                    <Input
                                        id="instruction"
                                        value={rewriteInstruction}
                                        onChange={(e) =>
                                            setRewriteInstruction(
                                                e.target.value,
                                            )
                                        }
                                        placeholder="E.g., Make it more descriptive, Change tone to ominous..."
                                    />
                                </div>
                                <div className="rounded bg-muted p-2 text-sm text-muted-foreground">
                                    "{rewriteSelection.substring(0, 100)}
                                    ..."
                                </div>
                            </div>
                            <DialogFooter>
                                <Button onClick={onRewrite} disabled={isBusy}>
                                    Rewrite
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    <Dialog open={expandOpen} onOpenChange={setExpandOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Expand Selection</DialogTitle>
                            </DialogHeader>
                            <div className="space-y-4">
                                <p>Expand this summary into a full scene?</p>
                                <div className="rounded bg-muted p-2 text-sm text-muted-foreground">
                                    "{expandSelection.substring(0, 100)}
                                    ..."
                                </div>
                            </div>
                            <DialogFooter>
                                <Button onClick={onExpand} disabled={isBusy}>
                                    Expand
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
            <div className="flex-1 overflow-y-auto bg-background">
                <Editor
                    content={content}
                    onChange={onChange}
                    onAnalyze={aiHandlers.handleAnalyze}
                    onSuggest={aiHandlers.handleSuggest}
                    onRewrite={handleRewriteRequest}
                    onExpand={handleExpandRequest}
                    isBusy={isBusy}
                />
            </div>
        </div>
    );
}
