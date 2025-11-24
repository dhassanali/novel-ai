import AppLayout from '@/layouts/app-layout';
import { Head, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useState, useRef } from 'react';
import { store as storeChapter, update as updateChapter, generate, analyze, suggest, rewrite, expand } from '@/actions/App/Http/Controllers/ChapterController';
import { store as storeDocument, storeLink } from '@/actions/App/Http/Controllers/SourceDocumentController';
import axios from 'axios';
import { Switch } from '@/components/ui/switch';
import Editor from '@/components/Editor';
import { Globe, FileText } from 'lucide-react';

interface Chapter {
    id: number;
    title: string;
    content: string;
    order: number;
}

interface SourceDocument {
    id: number;
    filename: string;
    status: string;
    type: string;
}

interface Novel {
    id: number;
    title: string;
    description: string;
    genre: string;
    chapters: Chapter[];
    source_documents: SourceDocument[];
}

interface Props {
    novel: Novel;
}

export default function Show({ novel }: Props) {
    const [activeChapter, setActiveChapter] = useState<Chapter | null>(novel.chapters[0] || null);
    const [content, setContent] = useState(activeChapter?.content || '');
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [generateOpen, setGenerateOpen] = useState(false);
    const [prompt, setPrompt] = useState('');
    const [generating, setGenerating] = useState(false);
    const [webSearchEnabled, setWebSearchEnabled] = useState(false);

    const createChapter = () => {
        router.post(storeChapter.url({ novel: novel.id }), {
            title: `Chapter ${novel.chapters.length + 1}`,
        }, {
            onSuccess: () => {
                // Ideally select the new chapter, but a page reload happens so we might need to handle that
            }
        });
    };

    const saveChapter = () => {
        if (!activeChapter) return;
        router.put(updateChapter.url({ novel: novel.id, chapter: activeChapter.id }), {
            title: activeChapter.title,
            content: content,
        }, {
            preserveScroll: true,
        });
    };

    const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        router.post(storeDocument.url({ novel: novel.id }), {
            file: file
        }, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const handleGenerate = async () => {
        if (!activeChapter) return;
        setGenerating(true);
        try {
            const response = await axios.post(generate.url({ novel: novel.id, chapter: activeChapter.id }), {
                prompt: prompt,
                mode: webSearchEnabled ? 'web' : 'context'
            });
            setContent(prev => prev + (prev ? '\n\n' : '') + response.data.text);
            setGenerateOpen(false);
            setPrompt('');
        } catch (error) {
            console.error(error);
        } finally {
            setGenerating(false);
        }
    };

    const [analysisOpen, setAnalysisOpen] = useState(false);
    const [analysisResult, setAnalysisResult] = useState('');
    const [isBusy, setIsBusy] = useState(false);

    const handleAnalyze = async (text: string) => {
        if (!activeChapter) return;
        setIsBusy(true);
        try {
            const response = await axios.post(analyze.url({ novel: novel.id, chapter: activeChapter.id }), {
                selection: text
            });
            setAnalysisResult(response.data.analysis);
            setAnalysisOpen(true);
        } catch (error) {
            console.error(error);
        } finally {
            setIsBusy(false);
        }
    };

    const handleSuggest = async (text: string) => {
        if (!activeChapter) return;
        setIsBusy(true);
        try {
            const response = await axios.post(suggest.url({ novel: novel.id, chapter: activeChapter.id }), {
                context: text
            });
            setContent(prev => prev + ' ' + response.data.suggestion);
        } catch (error) {
            console.error(error);
        } finally {
            setIsBusy(false);
        }
    };

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

    const handleRewrite = async () => {
        if (!activeChapter) return;
        setIsBusy(true);
        try {
            const response = await axios.post(rewrite.url({ novel: novel.id, chapter: activeChapter.id }), {
                selection: rewriteSelection,
                instruction: rewriteInstruction
            });
            // Replace the selected text with the rewritten text
            // Note: This is a simple replacement. For a real editor, we'd want to use Tiptap commands to replace the selection range.
            // Since we don't have direct access to the editor instance here, we might need to rethink this or just append for now.
            // Ideally, the Editor component should handle the replacement if we pass the result back, or we update the content state.
            // For now, let's append it to a "Rewrite Result" dialog or just replace the content if it matches (risky).
            // Better approach: Show the result in a dialog and let the user copy it.
            setAnalysisResult("Rewritten Text:\n\n" + response.data.rewritten);
            setAnalysisOpen(true);
            setRewriteOpen(false);
            setRewriteInstruction('');
        } catch (error) {
            console.error(error);
        } finally {
            setIsBusy(false);
        }
    };

    const handleExpand = async () => {
        if (!activeChapter) return;
        setIsBusy(true);
        try {
            const response = await axios.post(expand.url({ novel: novel.id, chapter: activeChapter.id }), {
                selection: expandSelection
            });
            setAnalysisResult("Expanded Scene:\n\n" + response.data.expanded);
            setAnalysisOpen(true);
            setExpandOpen(false);
        } catch (error) {
            console.error(error);
        } finally {
            setIsBusy(false);
        }
    };

    return (
        <AppLayout breadcrumbs={[
            { title: 'Novels', href: '/novels' },
            { title: novel.title, href: `/novels/${novel.id}` }
        ]}>
            <Head title={novel.title} />
            <div className="flex h-[calc(100vh-4rem)]">
                {/* Sidebar */}
                <div className="w-80 border-r bg-muted/10 flex flex-col">
                    <div className="p-4 border-b">
                        <h2 className="font-semibold mb-2">Chapters</h2>
                        <Button className="w-full" size="sm" variant="outline" onClick={createChapter}>New Chapter</Button>
                    </div>
                    <div className="flex-1 overflow-y-auto p-2 space-y-1">
                        {novel.chapters.map(chapter => (
                            <Button
                                key={chapter.id}
                                variant={activeChapter?.id === chapter.id ? 'secondary' : 'ghost'}
                                className="w-full justify-start"
                                onClick={() => {
                                    setActiveChapter(chapter);
                                    setContent(chapter.content || '');
                                }}
                            >
                                {chapter.title}
                            </Button>
                        ))}
                        {novel.chapters.length === 0 && (
                            <div className="text-sm text-muted-foreground text-center py-4">
                                No chapters yet.
                            </div>
                        )}
                    </div>
                    <Separator />
                    <div className="p-4 border-t h-1/3 flex flex-col">
                        <div className="flex justify-between items-center mb-2">
                            <h2 className="font-semibold">Sources</h2>
                            <div className="flex gap-1">
                                <input
                                    type="file"
                                    ref={fileInputRef}
                                    className="hidden"
                                    onChange={handleFileUpload}
                                    accept=".pdf,.txt,.md,.csv"
                                    multiple
                                />
                                <Button size="icon" variant="ghost" className="h-6 w-6" onClick={() => fileInputRef.current?.click()}>
                                    <span className="sr-only">Upload File</span>
                                    +
                                </Button>
                                <Dialog>
                                    <DialogTrigger asChild>
                                        <Button size="icon" variant="ghost" className="h-6 w-6">
                                            <span className="sr-only">Add Link</span>
                                            <Globe className="h-4 w-4" />
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Add Web Link</DialogTitle>
                                        </DialogHeader>
                                        <form onSubmit={(e) => {
                                            e.preventDefault();
                                            const formData = new FormData(e.currentTarget);
                                            const url = formData.get('url') as string;
                                            if (url) {
                                                router.post(storeLink.url({ novel: novel.id }), { url });
                                                (e.target as HTMLFormElement).reset();
                                            }
                                        }}>
                                            <div className="grid gap-4 py-4">
                                                <div className="grid grid-cols-4 items-center gap-4">
                                                    <Label htmlFor="url" className="text-right">
                                                        URL
                                                    </Label>
                                                    <Input
                                                        id="url"
                                                        name="url"
                                                        placeholder="https://example.com"
                                                        className="col-span-3"
                                                        required
                                                    />
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <Button type="submit">Add Link</Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        </div>
                        <div className="flex-1 overflow-y-auto space-y-1">
                            {novel.source_documents.map(doc => (
                                <div key={doc.id} className="text-sm py-1 px-2 rounded hover:bg-accent/50 truncate flex justify-between items-center">
                                    <span className="truncate flex items-center gap-2">
                                        {doc.type === 'web_link' ? <Globe className="h-3 w-3" /> : <FileText className="h-3 w-3" />}
                                        {doc.filename}
                                    </span>
                                    <span className="text-xs text-muted-foreground">{doc.status}</span>
                                </div>
                            ))}
                            {novel.source_documents.length === 0 && (
                                <div className="text-sm text-muted-foreground text-center py-4">
                                    No source documents.
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Main Content */}
                <div className="flex-1 flex flex-col">
                    {activeChapter ? (
                        <>
                            <div className="p-4 border-b flex justify-between items-center bg-background">
                                <h1 className="text-xl font-bold">{activeChapter.title}</h1>
                                <div className="flex gap-2">
                                    <Button variant="outline" onClick={saveChapter}>Save</Button>
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
                                                        onChange={e => setPrompt(e.target.value)}
                                                        placeholder="Describe what happens next..."
                                                    />
                                                </div>
                                                <div className="flex items-center space-x-2">
                                                    <Switch
                                                        id="web-search"
                                                        checked={webSearchEnabled}
                                                        onCheckedChange={setWebSearchEnabled}
                                                    />
                                                    <Label htmlFor="web-search">Enable Web Search</Label>
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <Button onClick={handleGenerate} disabled={generating}>
                                                    {generating ? 'Generating...' : 'Generate'}
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>

                                    <Dialog open={analysisOpen} onOpenChange={setAnalysisOpen}>
                                        <DialogContent className="max-w-2xl">
                                            <DialogHeader>
                                                <DialogTitle>AI Result</DialogTitle>
                                            </DialogHeader>
                                            <div className="max-h-[60vh] overflow-y-auto whitespace-pre-wrap">
                                                {analysisResult}
                                            </div>
                                            <DialogFooter>
                                                <Button onClick={() => setAnalysisOpen(false)}>Close</Button>
                                                <Button variant="secondary" onClick={() => {
                                                    navigator.clipboard.writeText(analysisResult.replace(/^(Rewritten Text:|Expanded Scene:)\n\n/, ''));
                                                    setAnalysisOpen(false);
                                                }}>Copy</Button>
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
                                                    <Label htmlFor="instruction">Instructions</Label>
                                                    <Input
                                                        id="instruction"
                                                        value={rewriteInstruction}
                                                        onChange={e => setRewriteInstruction(e.target.value)}
                                                        placeholder="E.g., Make it more descriptive, Change tone to ominous..."
                                                    />
                                                </div>
                                                <div className="text-sm text-muted-foreground p-2 bg-muted rounded">
                                                    "{rewriteSelection.substring(0, 100)}..."
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <Button onClick={handleRewrite} disabled={isBusy}>Rewrite</Button>
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
                                                <div className="text-sm text-muted-foreground p-2 bg-muted rounded">
                                                    "{expandSelection.substring(0, 100)}..."
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <Button onClick={handleExpand} disabled={isBusy}>Expand</Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </div>
                            </div>
                            <div className="flex-1 overflow-y-auto bg-background">
                                <Editor
                                    content={content}
                                    onChange={setContent}
                                    onAnalyze={handleAnalyze}
                                    onSuggest={handleSuggest}
                                    onRewrite={handleRewriteRequest}
                                    onExpand={handleExpandRequest}
                                    isBusy={isBusy}
                                />
                            </div>
                        </>
                    ) : (
                        <div className="flex-1 flex items-center justify-center text-muted-foreground bg-muted/5">
                            Select or create a chapter to start writing.
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
