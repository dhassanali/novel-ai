import {
    store as storeChapter,
    update as updateChapter,
} from '@/actions/App/Http/Controllers/ChapterController';
import {
    destroy as destroyCharacter,
    store as storeCharacter,
    update as updateCharacter,
} from '@/actions/App/Http/Controllers/CharacterController';
import {
    destroy as destroyLocation,
    store as storeLocation,
    update as updateLocation,
} from '@/actions/App/Http/Controllers/LocationController';
import {
    deleteCover,
    exportMethod as exportNovelAction,
    update as updateNovel,
    uploadCover,
} from '@/actions/App/Http/Controllers/NovelController';
import {
    store as storeDocument,
    storeLink,
} from '@/actions/App/Http/Controllers/SourceDocumentController';
import ChapterEditModal from '@/components/Novels/ChapterEditModal';
import ChapterEditor from '@/components/Novels/ChapterEditor';
import CharacterModal from '@/components/Novels/CharacterModal';
import LocationModal from '@/components/Novels/LocationModal';
import NovelEditModal from '@/components/Novels/NovelEditModal';
import NovelSidebar from '@/components/Novels/NovelSidebar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useAutoSave } from '@/hooks/useAutoSave';
import { useNovelAI } from '@/hooks/useNovelAI';
import AppLayout from '@/layouts/app-layout';
import { Chapter, Character, Location, Novel } from '@/types/novel';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { useCallback, useState } from 'react';

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
    const [showSidebar, setShowSidebar] = useState(true);

    // Error State
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

    // AI Hook
    const aiHandlers = useNovelAI({
        novel,
        activeChapter,
        onUpdateContent: (newContent) =>
            setContent((prev) => prev + (prev ? '\n\n' : '') + newContent),
        onError: handleError,
    });

    // Character State
    const [characterOpen, setCharacterOpen] = useState(false);
    const [editingCharacter, setEditingCharacter] = useState<Character | null>(
        null,
    );

    const openCharacterModal = (char?: Character) => {
        setEditingCharacter(char || null);
        setCharacterOpen(true);
    };

    const handleSaveCharacter = (data: Partial<Character>) => {
        if (editingCharacter) {
            router.put(
                updateCharacter.url({ novel: novel.id, character: editingCharacter.id }),
                data,
                {
                    onSuccess: () => setCharacterOpen(false),
                },
            );
        } else {
            router.post(storeCharacter.url({ novel: novel.id }), data, {
                onSuccess: () => setCharacterOpen(false),
            });
        }
    };

    const handleDeleteCharacter = (char: Character) => {
        if (confirm('Are you sure you want to delete this character?')) {
            router.delete(destroyCharacter.url({ novel: novel.id, character: char.id }));
        }
    };

    // Location State
    const [locationOpen, setLocationOpen] = useState(false);
    const [editingLocation, setEditingLocation] = useState<Location | null>(
        null,
    );

    const openLocationModal = (loc?: Location) => {
        setEditingLocation(loc || null);
        setLocationOpen(true);
    };

    const handleSaveLocation = (data: Partial<Location>) => {
        if (editingLocation) {
            router.put(
                updateLocation.url({ novel: novel.id, location: editingLocation.id }),
                data,
                {
                    onSuccess: () => setLocationOpen(false),
                },
            );
        } else {
            router.post(storeLocation.url({ novel: novel.id }), data, {
                onSuccess: () => setLocationOpen(false),
            });
        }
    };

    const handleDeleteLocation = (loc: Location) => {
        if (confirm('Are you sure you want to delete this location?')) {
            router.delete(destroyLocation.url({ novel: novel.id, location: loc.id }));
        }
    };

    // Novel Edit State
    const [editNovelOpen, setEditNovelOpen] = useState(false);

    const handleUpdateNovel = (data: {
        title: string;
        genre: string;
        description: string;
        word_count_goal: number | null;
    }) => {
        router.put(updateNovel.url({ novel: novel.id }), data, {
            onSuccess: () => setEditNovelOpen(false),
        });
    };

    const handleExport = () => {
        window.location.href = exportNovelAction.url({ novel: novel.id });
    };

    // Cover Image Handlers
    const handleCoverUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        router.post(
            uploadCover.url({ novel: novel.id }),
            {
                cover: file,
            },
            {
                forceFormData: true,
                preserveScroll: true,
            },
        );
    };

    const handleCoverDelete = () => {
        if (confirm('Are you sure you want to delete the cover image?')) {
            router.delete(deleteCover.url({ novel: novel.id }), {
                preserveScroll: true,
            });
        }
    };

    // Chapter Edit State
    const [editChapterOpen, setEditChapterOpen] = useState(false);

    const handleUpdateChapter = (data: {
        title: string;
        status: import('@/types/novel').ChapterStatus;
        pov_character_id: number | null;
    }) => {
        if (!activeChapter) return;
        router.put(
            updateChapter.url({ novel: novel.id, chapter: activeChapter.id }),
            data,
            {
                onSuccess: () => {
                    setEditChapterOpen(false);
                    setActiveChapter((prev) =>
                        prev ? { ...prev, ...data } : null,
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
                    // Ideally select the new chapter
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

    // Auto-save
    useAutoSave(
        content,
        activeChapter?.content || '',
        () => {
            setSaveStatus('unsaved');
            saveChapter();
        },
        2000,
    );

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

    const handleAddLink = (url: string) => {
        router.post(storeLink.url({ novel: novel.id }), { url });
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

            <ChapterEditModal
                open={editChapterOpen}
                onOpenChange={setEditChapterOpen}
                chapter={activeChapter}
                characters={novel.characters}
                onSave={handleUpdateChapter}
            />

            <NovelEditModal
                open={editNovelOpen}
                onOpenChange={setEditNovelOpen}
                novel={novel}
                onSave={handleUpdateNovel}
            />

            <div className="flex h-[calc(100vh-4rem)]">
                {showSidebar && (
                    <NovelSidebar
                        novel={novel}
                        activeChapter={activeChapter}
                        onChapterSelect={(chapter) => {
                            setActiveChapter(chapter);
                            setContent(chapter.content || '');
                        }}
                        onEditNovel={() => setEditNovelOpen(true)}
                        onCreateChapter={createChapter}
                        onUploadCover={handleCoverUpload}
                        onDeleteCover={handleCoverDelete}
                        onUploadDocument={handleFileUpload}
                        onAddLink={handleAddLink}
                        onManageCharacter={openCharacterModal}
                        onDeleteCharacter={handleDeleteCharacter}
                        onManageLocation={openLocationModal}
                        onDeleteLocation={handleDeleteLocation}
                        onExport={handleExport}
                    />
                )}

                <ChapterEditor
                    activeChapter={activeChapter}
                    content={content}
                    saveStatus={saveStatus}
                    isBusy={aiHandlers.isBusy}
                    onChange={setContent}
                    onSave={saveChapter}
                    onEditTitle={() => setEditChapterOpen(true)}
                    onToggleSidebar={() => setShowSidebar(!showSidebar)}
                    showSidebar={showSidebar}
                    aiHandlers={aiHandlers}
                />
            </div>

            <CharacterModal
                open={characterOpen}
                onOpenChange={setCharacterOpen}
                character={editingCharacter}
                onSave={handleSaveCharacter}
            />

            <LocationModal
                open={locationOpen}
                onOpenChange={setLocationOpen}
                location={editingLocation}
                onSave={handleSaveLocation}
            />
        </AppLayout>
    );
}
