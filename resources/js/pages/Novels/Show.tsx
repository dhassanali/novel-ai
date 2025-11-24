import {
    analyze,
    expand,
    generate,
    rewrite,
    store as storeChapter,
    suggest,
    update as updateChapter,
} from '@/actions/App/Http/Controllers/ChapterController';
import { update as updateNovel } from '@/actions/App/Http/Controllers/NovelController';
import {
    store as storeDocument,
    storeLink,
} from '@/actions/App/Http/Controllers/SourceDocumentController';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import {
    Check,
    FileText,
    Globe,
    Loader2,
    MapPin,
    PanelLeft,
    Pencil,
    Plus,
    Trash2,
    User,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

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
    characters: Character[];
    locations: Location[];
}

interface Character {
    id: number;
    name: string;
    description: string;
    role: string;
}

interface Location {
    id: number;
    name: string;
    description: string;
}

interface Props {
    novel: Novel;
}

export default function Show({ novel }: Props) {
    const [activeChapter, setActiveChapter] = useState<Chapter | null>(
        novel.chapters[0] || null,
    );
    const [content, setContent] = useState(activeChapter?.content || '');
    const [saveStatus, setSaveStatus] = useState<
        'saved' | 'saving' | 'unsaved'
    >('saved');
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [generateOpen, setGenerateOpen] = useState(false);
    const [prompt, setPrompt] = useState('');
    const [generating, setGenerating] = useState(false);
    const [webSearchEnabled, setWebSearchEnabled] = useState(false);
    const [showSidebar, setShowSidebar] = useState(true);

    // Character & Location State
    const [characterOpen, setCharacterOpen] = useState(false);
    const [editingCharacter, setEditingCharacter] = useState<Character | null>(
        null,
    );
    const [charName, setCharName] = useState('');
    const [charRole, setCharRole] = useState('');
    const [charDesc, setCharDesc] = useState('');

    const [locationOpen, setLocationOpen] = useState(false);
    const [editingLocation, setEditingLocation] = useState<Location | null>(
        null,
    );
    const [locName, setLocName] = useState('');
    const [locDesc, setLocDesc] = useState('');

    const openCharacterModal = (char?: Character) => {
        if (char) {
            setEditingCharacter(char);
            setCharName(char.name);
            setCharRole(char.role || '');
            setCharDesc(char.description || '');
        } else {
            setEditingCharacter(null);
            setCharName('');
            setCharRole('');
            setCharDesc('');
        }
        setCharacterOpen(true);
    };

    const openLocationModal = (loc?: Location) => {
        if (loc) {
            setEditingLocation(loc);
            setLocName(loc.name);
            setLocDesc(loc.description || '');
        } else {
            setEditingLocation(null);
            setLocName('');
            setLocDesc('');
        }
        setLocationOpen(true);
    };

    const handleSaveCharacter = () => {
        if (editingCharacter) {
            router.put(
                `/novels/${novel.id}/characters/${editingCharacter.id}`,
                {
                    name: charName,
                    role: charRole,
                    description: charDesc,
                },
                {
                    onSuccess: () => setCharacterOpen(false),
                },
            );
        } else {
            router.post(
                `/novels/${novel.id}/characters`,
                {
                    name: charName,
                    role: charRole,
                    description: charDesc,
                },
                {
                    onSuccess: () => setCharacterOpen(false),
                },
            );
        }
    };

    const handleDeleteCharacter = (char: Character) => {
        if (confirm('Are you sure you want to delete this character?')) {
            router.delete(`/novels/${novel.id}/characters/${char.id}`);
        }
    };

    const handleSaveLocation = () => {
        if (editingLocation) {
            router.put(
                `/novels/${novel.id}/locations/${editingLocation.id}`,
                {
                    name: locName,
                    description: locDesc,
                },
                {
                    onSuccess: () => setLocationOpen(false),
                },
            );
        } else {
            router.post(
                `/novels/${novel.id}/locations`,
                {
                    name: locName,
                    description: locDesc,
                },
                {
                    onSuccess: () => setLocationOpen(false),
                },
            );
        }
    };

    const handleDeleteLocation = (loc: Location) => {
        if (confirm('Are you sure you want to delete this location?')) {
            router.delete(`/novels/${novel.id}/locations/${loc.id}`);
        }
    };

    // Novel Edit State
    const [editNovelOpen, setEditNovelOpen] = useState(false);
    const [novelTitle, setNovelTitle] = useState(novel.title);
    const [novelDescription, setNovelDescription] = useState(
        novel.description || '',
    );
    const [novelGenre, setNovelGenre] = useState(novel.genre || '');

    const handleUpdateNovel = () => {
        router.put(
            updateNovel.url({ novel: novel.id }),
            {
                title: novelTitle,
                description: novelDescription,
                genre: novelGenre,
            },
            {
                onSuccess: () => setEditNovelOpen(false),
            },
        );
    };

    // Chapter Edit State
    const [editChapterOpen, setEditChapterOpen] = useState(false);
    const [chapterTitle, setChapterTitle] = useState('');

    const handleUpdateChapterTitle = () => {
        if (!activeChapter) return;
        router.put(
            updateChapter.url({ novel: novel.id, chapter: activeChapter.id }),
            {
                title: chapterTitle,
            },
            {
                onSuccess: () => {
                    setEditChapterOpen(false);
                    // Update the active chapter title locally to reflect changes immediately if needed,
                    // though Inertia reload should handle it.
                    setActiveChapter((prev) =>
                        prev ? { ...prev, title: chapterTitle } : null,
                    );
                },
            },
        );
    };

    const createChapter = () => {
        router.post(
            storeChapter.url({ novel: novel.id }),
            {
                title: `Chapter ${novel.chapters.length + 1}`,
            },
            {
                onSuccess: () => {
                    // Ideally select the new chapter, but a page reload happens so we might need to handle that
                },
            },
        );
    };

    const saveChapter = useCallback(async () => {
        if (!activeChapter) return;
        setSaveStatus('saving');
        try {
            await axios.put(
                updateChapter.url({
                    novel: novel.id,
                    chapter: activeChapter.id,
                }),
                {
                    title: activeChapter.title,
                    content: content,
                },
            );
            setSaveStatus('saved');
        } catch (error) {
            setSaveStatus('unsaved');
            handleError(error);
        }
    }, [activeChapter, content, novel.id]);

    // Auto-save effect
    useEffect(() => {
        if (!activeChapter || content === activeChapter.content) return;

        setSaveStatus('unsaved');

        const timeoutId = setTimeout(() => {
            saveChapter();
        }, 2000); // Auto-save after 2 seconds of inactivity

        return () => clearTimeout(timeoutId);
    }, [content, activeChapter, saveChapter]);

    const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        router.post(
            storeDocument.url({ novel: novel.id }),
            {
                file: file,
            },
            {
                forceFormData: true,
                preserveScroll: true,
            },
        );
    };

    const [errorOpen, setErrorOpen] = useState(false);
    const [errorMessage, setErrorMessage] = useState('');

    const handleError = (error: unknown) => {
        console.error(error);
        if (axios.isAxiosError(error)) {
            setErrorMessage(
                error.response?.data?.message ||
                    error.message ||
                    'An unexpected error occurred.',
            );
        } else if (error instanceof Error) {
            setErrorMessage(error.message);
        } else {
            setErrorMessage('An unexpected error occurred.');
        }
        setErrorOpen(true);
    };

    const handleGenerate = async () => {
        if (!activeChapter) return;
        setGenerating(true);
        try {
            const response = await axios.post(
                generate.url({ novel: novel.id, chapter: activeChapter.id }),
                {
                    prompt: prompt,
                    mode: webSearchEnabled ? 'web' : 'context',
                },
            );
            setContent(
                (prev) => prev + (prev ? '\n\n' : '') + response.data.text,
            );
            setGenerateOpen(false);
            setPrompt('');
        } catch (error) {
            handleError(error);
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
            const response = await axios.post(
                analyze.url({ novel: novel.id, chapter: activeChapter.id }),
                {
                    selection: text,
                },
            );
            setAnalysisResult(response.data.analysis);
            setAnalysisOpen(true);
        } catch (error) {
            handleError(error);
        } finally {
            setIsBusy(false);
        }
    };

    const handleSuggest = async (text: string) => {
        if (!activeChapter) return;
        setIsBusy(true);
        try {
            const response = await axios.post(
                suggest.url({ novel: novel.id, chapter: activeChapter.id }),
                {
                    context: text,
                },
            );
            setContent((prev) => prev + ' ' + response.data.suggestion);
        } catch (error) {
            handleError(error);
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
            const response = await axios.post(
                rewrite.url({ novel: novel.id, chapter: activeChapter.id }),
                {
                    selection: rewriteSelection,
                    instruction: rewriteInstruction,
                },
            );
            // Replace the selected text with the rewritten text
            // Note: This is a simple replacement. For a real editor, we'd want to use Tiptap commands to replace the selection range.
            // Since we don't have direct access to the editor instance here, we might need to rethink this or just append for now.
            // Ideally, the Editor component should handle the replacement if we pass the result back, or we update the content state.
            // For now, let's append it to a "Rewrite Result" dialog or just replace the content if it matches (risky).
            // Better approach: Show the result in a dialog and let the user copy it.
            setAnalysisResult('Rewritten Text:\n\n' + response.data.rewritten);
            setAnalysisOpen(true);
            setRewriteOpen(false);
            setRewriteInstruction('');
        } catch (error) {
            handleError(error);
        } finally {
            setIsBusy(false);
        }
    };

    const handleExpand = async () => {
        if (!activeChapter) return;
        setIsBusy(true);
        try {
            const response = await axios.post(
                expand.url({ novel: novel.id, chapter: activeChapter.id }),
                {
                    selection: expandSelection,
                },
            );
            setAnalysisResult('Expanded Scene:\n\n' + response.data.expanded);
            setAnalysisOpen(true);
            setExpandOpen(false);
        } catch (error) {
            handleError(error);
        } finally {
            setIsBusy(false);
        }
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Novels', href: '/novels' },
                { title: novel.title, href: `/novels/${novel.id}` },
            ]}
        >
            <Head title={novel.title} />
            <Dialog open={errorOpen} onOpenChange={setErrorOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="text-destructive">
                            Error
                        </DialogTitle>
                    </DialogHeader>
                    <div className="text-sm">{errorMessage}</div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setErrorOpen(false)}
                        >
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            <Dialog open={editChapterOpen} onOpenChange={setEditChapterOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Chapter Title</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="chapter-title">Title</Label>
                            <Input
                                id="chapter-title"
                                value={chapterTitle}
                                onChange={(e) =>
                                    setChapterTitle(e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button onClick={handleUpdateChapterTitle}>
                            Save Changes
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            <Dialog open={editNovelOpen} onOpenChange={setEditNovelOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Novel Details</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="novel-title">Title</Label>
                            <Input
                                id="novel-title"
                                value={novelTitle}
                                onChange={(e) => setNovelTitle(e.target.value)}
                            />
                        </div>
                        <div>
                            <Label htmlFor="novel-genre">Genre</Label>
                            <Input
                                id="novel-genre"
                                value={novelGenre}
                                onChange={(e) => setNovelGenre(e.target.value)}
                                placeholder="Fantasy, Sci-Fi, etc."
                            />
                        </div>
                        <div>
                            <Label htmlFor="novel-description">
                                Description
                            </Label>
                            <Textarea
                                id="novel-description"
                                value={novelDescription}
                                onChange={(e) =>
                                    setNovelDescription(e.target.value)
                                }
                                placeholder="A brief summary of your novel..."
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button onClick={handleUpdateNovel}>
                            Save Changes
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            <div className="flex h-[calc(100vh-4rem)]">
                {/* Sidebar */}
                {showSidebar && (
                    <div className="flex w-80 flex-col border-r bg-muted/10">
                        <div className="border-b p-4">
                            <div className="mb-4 flex items-center justify-between">
                                <h2
                                    className="truncate pr-2 font-semibold"
                                    title={novel.title}
                                >
                                    {novel.title}
                                </h2>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    className="h-6 w-6 shrink-0"
                                    onClick={() => setEditNovelOpen(true)}
                                >
                                    <Pencil className="h-3 w-3" />
                                </Button>
                            </div>
                            <Button
                                className="w-full"
                                size="sm"
                                variant="outline"
                                onClick={createChapter}
                            >
                                New Chapter
                            </Button>
                        </div>
                        <div className="flex-1 space-y-1 overflow-y-auto p-2">
                            {novel.chapters.map((chapter) => (
                                <Button
                                    key={chapter.id}
                                    variant={
                                        activeChapter?.id === chapter.id
                                            ? 'secondary'
                                            : 'ghost'
                                    }
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
                                <div className="py-4 text-center text-sm text-muted-foreground">
                                    No chapters yet.
                                </div>
                            )}
                        </div>
                        <Separator />
                        <div className="flex h-1/3 flex-col border-t p-4">
                            <div className="mb-2 flex items-center justify-between">
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
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        className="h-6 w-6"
                                        onClick={() =>
                                            fileInputRef.current?.click()
                                        }
                                    >
                                        <span className="sr-only">
                                            Upload File
                                        </span>
                                        +
                                    </Button>
                                    <Dialog>
                                        <DialogTrigger asChild>
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                className="h-6 w-6"
                                            >
                                                <span className="sr-only">
                                                    Add Link
                                                </span>
                                                <Globe className="h-4 w-4" />
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>
                                                    Add Web Link
                                                </DialogTitle>
                                            </DialogHeader>
                                            <form
                                                onSubmit={(e) => {
                                                    e.preventDefault();
                                                    const formData =
                                                        new FormData(
                                                            e.currentTarget,
                                                        );
                                                    const url = formData.get(
                                                        'url',
                                                    ) as string;
                                                    if (url) {
                                                        router.post(
                                                            storeLink.url({
                                                                novel: novel.id,
                                                            }),
                                                            { url },
                                                        );
                                                        (
                                                            e.target as HTMLFormElement
                                                        ).reset();
                                                    }
                                                }}
                                            >
                                                <div className="grid gap-4 py-4">
                                                    <div className="grid grid-cols-4 items-center gap-4">
                                                        <Label
                                                            htmlFor="url"
                                                            className="text-right"
                                                        >
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
                                                    <Button type="submit">
                                                        Add Link
                                                    </Button>
                                                </DialogFooter>
                                            </form>
                                        </DialogContent>
                                    </Dialog>
                                </div>
                            </div>
                            <div className="flex-1 space-y-1 overflow-y-auto">
                                {novel.source_documents.map((doc) => (
                                    <div
                                        key={doc.id}
                                        className="flex items-center justify-between truncate rounded px-2 py-1 text-sm hover:bg-accent/50"
                                    >
                                        <span className="flex items-center gap-2 truncate">
                                            {doc.type === 'web_link' ? (
                                                <Globe className="h-3 w-3" />
                                            ) : (
                                                <FileText className="h-3 w-3" />
                                            )}
                                            {doc.filename}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {doc.status}
                                        </span>
                                    </div>
                                ))}
                                {novel.source_documents.length === 0 && (
                                    <div className="py-4 text-center text-sm text-muted-foreground">
                                        No source documents.
                                    </div>
                                )}
                            </div>
                            <Separator />
                            <div className="flex h-1/3 flex-col border-t p-4">
                                <div className="mb-2 flex items-center justify-between">
                                    <h2 className="font-semibold">
                                        Characters
                                    </h2>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        className="h-6 w-6"
                                        onClick={() => openCharacterModal()}
                                    >
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </div>
                                <div className="flex-1 space-y-1 overflow-y-auto">
                                    {novel.characters?.map((char) => (
                                        <div
                                            key={char.id}
                                            className="group flex items-center justify-between rounded px-2 py-1 text-sm hover:bg-accent/50"
                                        >
                                            <div
                                                className="flex cursor-pointer items-center gap-2 truncate"
                                                onClick={() =>
                                                    openCharacterModal(char)
                                                }
                                            >
                                                <User className="h-3 w-3" />
                                                <span>{char.name}</span>
                                            </div>
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                className="h-4 w-4 opacity-0 group-hover:opacity-100"
                                                onClick={() =>
                                                    handleDeleteCharacter(char)
                                                }
                                            >
                                                <Trash2 className="h-3 w-3 text-destructive" />
                                            </Button>
                                        </div>
                                    ))}
                                    {(!novel.characters ||
                                        novel.characters.length === 0) && (
                                        <div className="py-2 text-center text-xs text-muted-foreground">
                                            No characters.
                                        </div>
                                    )}
                                </div>
                            </div>
                            <Separator />
                            <div className="flex h-1/3 flex-col border-t p-4">
                                <div className="mb-2 flex items-center justify-between">
                                    <h2 className="font-semibold">Locations</h2>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        className="h-6 w-6"
                                        onClick={() => openLocationModal()}
                                    >
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </div>
                                <div className="flex-1 space-y-1 overflow-y-auto">
                                    {novel.locations?.map((loc) => (
                                        <div
                                            key={loc.id}
                                            className="group flex items-center justify-between rounded px-2 py-1 text-sm hover:bg-accent/50"
                                        >
                                            <div
                                                className="flex cursor-pointer items-center gap-2 truncate"
                                                onClick={() =>
                                                    openLocationModal(loc)
                                                }
                                            >
                                                <MapPin className="h-3 w-3" />
                                                <span>{loc.name}</span>
                                            </div>
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                className="h-4 w-4 opacity-0 group-hover:opacity-100"
                                                onClick={() =>
                                                    handleDeleteLocation(loc)
                                                }
                                            >
                                                <Trash2 className="h-3 w-3 text-destructive" />
                                            </Button>
                                        </div>
                                    ))}
                                    {(!novel.locations ||
                                        novel.locations.length === 0) && (
                                        <div className="py-2 text-center text-xs text-muted-foreground">
                                            No locations.
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Main Content */}
                <div className="flex flex-1 flex-col">
                    {activeChapter ? (
                        <>
                            <div className="flex items-center justify-between border-b bg-background p-4">
                                <div className="flex items-center gap-2">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        onClick={() =>
                                            setShowSidebar(!showSidebar)
                                        }
                                        title={
                                            showSidebar
                                                ? 'Hide Sidebar'
                                                : 'Show Sidebar'
                                        }
                                    >
                                        <PanelLeft className="h-4 w-4" />
                                    </Button>
                                    <h1 className="text-xl font-bold">
                                        {activeChapter.title}
                                    </h1>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        className="h-6 w-6 shrink-0"
                                        onClick={() => {
                                            setChapterTitle(
                                                activeChapter.title,
                                            );
                                            setEditChapterOpen(true);
                                        }}
                                    >
                                        <Pencil className="h-3 w-3" />
                                    </Button>
                                </div>
                                <div className="flex items-center gap-2">
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
                                        onClick={() => saveChapter()}
                                        disabled={saveStatus === 'saving'}
                                    >
                                        Save
                                    </Button>
                                    <Dialog
                                        open={generateOpen}
                                        onOpenChange={setGenerateOpen}
                                    >
                                        <DialogTrigger asChild>
                                            <Button>Generate AI</Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>
                                                    Generate Content
                                                </DialogTitle>
                                            </DialogHeader>
                                            <div className="space-y-4">
                                                <div>
                                                    <Label htmlFor="prompt">
                                                        Prompt
                                                    </Label>
                                                    <Input
                                                        id="prompt"
                                                        value={prompt}
                                                        onChange={(e) =>
                                                            setPrompt(
                                                                e.target.value,
                                                            )
                                                        }
                                                        placeholder="Describe what happens next..."
                                                    />
                                                </div>
                                                <div className="flex items-center space-x-2">
                                                    <Switch
                                                        id="web-search"
                                                        checked={
                                                            webSearchEnabled
                                                        }
                                                        onCheckedChange={
                                                            setWebSearchEnabled
                                                        }
                                                    />
                                                    <Label htmlFor="web-search">
                                                        Enable Web Search
                                                    </Label>
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <Button
                                                    onClick={handleGenerate}
                                                    disabled={generating}
                                                >
                                                    {generating
                                                        ? 'Generating...'
                                                        : 'Generate'}
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>

                                    <Dialog
                                        open={analysisOpen}
                                        onOpenChange={setAnalysisOpen}
                                    >
                                        <DialogContent className="max-w-2xl">
                                            <DialogHeader>
                                                <DialogTitle>
                                                    AI Result
                                                </DialogTitle>
                                            </DialogHeader>
                                            <div className="max-h-[60vh] overflow-y-auto whitespace-pre-wrap">
                                                {analysisResult}
                                            </div>
                                            <DialogFooter>
                                                <Button
                                                    onClick={() =>
                                                        setAnalysisOpen(false)
                                                    }
                                                >
                                                    Close
                                                </Button>
                                                <Button
                                                    variant="secondary"
                                                    onClick={() => {
                                                        navigator.clipboard.writeText(
                                                            analysisResult.replace(
                                                                /^(Rewritten Text:|Expanded Scene:)\n\n/,
                                                                '',
                                                            ),
                                                        );
                                                        setAnalysisOpen(false);
                                                    }}
                                                >
                                                    Copy
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>

                                    <Dialog
                                        open={rewriteOpen}
                                        onOpenChange={setRewriteOpen}
                                    >
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>
                                                    Rewrite Selection
                                                </DialogTitle>
                                            </DialogHeader>
                                            <div className="space-y-4">
                                                <div>
                                                    <Label htmlFor="instruction">
                                                        Instructions
                                                    </Label>
                                                    <Input
                                                        id="instruction"
                                                        value={
                                                            rewriteInstruction
                                                        }
                                                        onChange={(e) =>
                                                            setRewriteInstruction(
                                                                e.target.value,
                                                            )
                                                        }
                                                        placeholder="E.g., Make it more descriptive, Change tone to ominous..."
                                                    />
                                                </div>
                                                <div className="rounded bg-muted p-2 text-sm text-muted-foreground">
                                                    "
                                                    {rewriteSelection.substring(
                                                        0,
                                                        100,
                                                    )}
                                                    ..."
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <Button
                                                    onClick={handleRewrite}
                                                    disabled={isBusy}
                                                >
                                                    Rewrite
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>

                                    <Dialog
                                        open={expandOpen}
                                        onOpenChange={setExpandOpen}
                                    >
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>
                                                    Expand Selection
                                                </DialogTitle>
                                            </DialogHeader>
                                            <div className="space-y-4">
                                                <p>
                                                    Expand this summary into a
                                                    full scene?
                                                </p>
                                                <div className="rounded bg-muted p-2 text-sm text-muted-foreground">
                                                    "
                                                    {expandSelection.substring(
                                                        0,
                                                        100,
                                                    )}
                                                    ..."
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <Button
                                                    onClick={handleExpand}
                                                    disabled={isBusy}
                                                >
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
                        <div className="flex flex-1 items-center justify-center bg-muted/5 text-muted-foreground">
                            Select or create a chapter to start writing.
                        </div>
                    )}
                </div>
            </div>
            <Dialog open={characterOpen} onOpenChange={setCharacterOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editingCharacter
                                ? 'Edit Character'
                                : 'Add Character'}
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="char-name">Name</Label>
                            <Input
                                id="char-name"
                                value={charName}
                                onChange={(e) => setCharName(e.target.value)}
                                placeholder="Character Name"
                            />
                        </div>
                        <div>
                            <Label htmlFor="char-role">Role</Label>
                            <Input
                                id="char-role"
                                value={charRole}
                                onChange={(e) => setCharRole(e.target.value)}
                                placeholder="Protagonist, Antagonist, etc."
                            />
                        </div>
                        <div>
                            <Label htmlFor="char-desc">Description</Label>
                            <Textarea
                                id="char-desc"
                                value={charDesc}
                                onChange={(e) => setCharDesc(e.target.value)}
                                placeholder="Physical appearance, personality, etc."
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button onClick={handleSaveCharacter}>Save</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            <Dialog open={locationOpen} onOpenChange={setLocationOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editingLocation ? 'Edit Location' : 'Add Location'}
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="loc-name">Name</Label>
                            <Input
                                id="loc-name"
                                value={locName}
                                onChange={(e) => setLocName(e.target.value)}
                                placeholder="Location Name"
                            />
                        </div>
                        <div>
                            <Label htmlFor="loc-desc">Description</Label>
                            <Textarea
                                id="loc-desc"
                                value={locDesc}
                                onChange={(e) => setLocDesc(e.target.value)}
                                placeholder="Geography, atmosphere, significance, etc."
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button onClick={handleSaveLocation}>Save</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
