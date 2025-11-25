import BrainstormTab from '@/components/Novels/BrainstormTab';
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { Chapter, Character, Location, Novel } from '@/types/novel';
import {
    BookOpen,
    FileText,
    Globe,
    Lightbulb,
    MapPin,
    Pencil,
    Plus,
    Trash2,
    Upload,
    User,
    Users,
} from 'lucide-react';
import { useRef, useState } from 'react';

interface NovelSidebarProps {
    novel: Novel;
    activeChapter: Chapter | null;
    onChapterSelect: (chapter: Chapter) => void;
    onEditNovel: () => void;
    onCreateChapter: () => void;
    onUploadCover: (e: React.ChangeEvent<HTMLInputElement>) => void;
    onDeleteCover: () => void;
    onUploadDocument: (e: React.ChangeEvent<HTMLInputElement>) => void;
    onAddLink: (url: string) => void;
    onManageCharacter: (char?: Character) => void;
    onDeleteCharacter: (char: Character) => void;
    onManageLocation: (loc?: Location) => void;
    onDeleteLocation: (loc: Location) => void;
}

type Tab = 'chapters' | 'characters' | 'locations' | 'sources' | 'brainstorm';

export default function NovelSidebar({
    novel,
    activeChapter,
    onChapterSelect,
    onEditNovel,
    onCreateChapter,
    onUploadCover,
    onDeleteCover,
    onUploadDocument,
    onAddLink,
    onManageCharacter,
    onDeleteCharacter,
    onManageLocation,
    onDeleteLocation,
}: NovelSidebarProps) {
    const coverInputRef = useRef<HTMLInputElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [activeTab, setActiveTab] = useState<Tab>('chapters');

    return (
        <div className="flex w-80 flex-col border-r bg-muted/10">
            <div className="border-b p-4">
                <div className="flex items-start gap-4">
                    {/* Cover Image - Smaller */}
                    <div className="group relative h-24 w-16 shrink-0 overflow-hidden rounded-md bg-muted">
                        {novel.cover_image_url ? (
                            <img
                                src={novel.cover_image_url}
                                alt={novel.title}
                                className="h-full w-full object-cover"
                            />
                        ) : (
                            <div className="flex h-full w-full items-center justify-center text-muted-foreground">
                                <Upload className="h-4 w-4" />
                            </div>
                        )}
                        <div className="absolute inset-0 flex items-center justify-center gap-1 bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                            <Button
                                size="icon"
                                variant="secondary"
                                className="h-6 w-6"
                                onClick={() => coverInputRef.current?.click()}
                            >
                                <Upload className="h-3 w-3" />
                            </Button>
                            {novel.cover_image_url && (
                                <Button
                                    size="icon"
                                    variant="destructive"
                                    className="h-6 w-6"
                                    onClick={onDeleteCover}
                                >
                                    <Trash2 className="h-3 w-3" />
                                </Button>
                            )}
                        </div>
                        <input
                            type="file"
                            ref={coverInputRef}
                            className="hidden"
                            onChange={onUploadCover}
                            accept="image/jpeg,image/png,image/jpg,image/webp"
                        />
                    </div>

                    <div className="min-w-0 flex-1">
                        <div className="flex items-center justify-between">
                            <h2
                                className="truncate font-semibold"
                                title={novel.title}
                            >
                                {novel.title}
                            </h2>
                            <Button
                                size="icon"
                                variant="ghost"
                                className="h-6 w-6 shrink-0"
                                onClick={onEditNovel}
                            >
                                <Pencil className="h-3 w-3" />
                            </Button>
                        </div>
                        <div className="mt-1 text-xs text-muted-foreground">
                            {novel.total_word_count.toLocaleString()} words
                        </div>
                        <div className="text-xs text-muted-foreground">
                            {novel.chapters.length} chapters
                        </div>
                    </div>
                </div>
            </div>

            {/* Tabs */}
            <div className="flex border-b bg-background">
                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant={
                                    activeTab === 'chapters'
                                        ? 'secondary'
                                        : 'ghost'
                                }
                                className="flex-1 rounded-none border-b-2 border-transparent data-[state=active]:border-primary"
                                onClick={() => setActiveTab('chapters')}
                                data-state={
                                    activeTab === 'chapters'
                                        ? 'active'
                                        : 'inactive'
                                }
                            >
                                <BookOpen className="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Chapters</TooltipContent>
                    </Tooltip>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant={
                                    activeTab === 'characters'
                                        ? 'secondary'
                                        : 'ghost'
                                }
                                className="flex-1 rounded-none border-b-2 border-transparent data-[state=active]:border-primary"
                                onClick={() => setActiveTab('characters')}
                                data-state={
                                    activeTab === 'characters'
                                        ? 'active'
                                        : 'inactive'
                                }
                            >
                                <Users className="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Characters</TooltipContent>
                    </Tooltip>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant={
                                    activeTab === 'locations'
                                        ? 'secondary'
                                        : 'ghost'
                                }
                                className="flex-1 rounded-none border-b-2 border-transparent data-[state=active]:border-primary"
                                onClick={() => setActiveTab('locations')}
                                data-state={
                                    activeTab === 'locations'
                                        ? 'active'
                                        : 'inactive'
                                }
                            >
                                <MapPin className="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Locations</TooltipContent>
                    </Tooltip>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant={
                                    activeTab === 'sources'
                                        ? 'secondary'
                                        : 'ghost'
                                }
                                className="flex-1 rounded-none border-b-2 border-transparent data-[state=active]:border-primary"
                                onClick={() => setActiveTab('sources')}
                                data-state={
                                    activeTab === 'sources'
                                        ? 'active'
                                        : 'inactive'
                                }
                            >
                                <FileText className="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Sources</TooltipContent>
                    </Tooltip>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant={
                                    activeTab === 'brainstorm'
                                        ? 'secondary'
                                        : 'ghost'
                                }
                                className="flex-1 rounded-none border-b-2 border-transparent data-[state=active]:border-primary"
                                onClick={() => setActiveTab('brainstorm')}
                                data-state={
                                    activeTab === 'brainstorm'
                                        ? 'active'
                                        : 'inactive'
                                }
                            >
                                <Lightbulb className="h-4 w-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Brainstorm</TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>

            {/* Content Area */}
            <div className="flex-1 overflow-y-auto p-2">
                {activeTab === 'brainstorm' && <BrainstormTab novel={novel} />}

                {activeTab === 'chapters' && (
                    <div className="space-y-2">
                        <Button
                            className="w-full"
                            size="sm"
                            variant="outline"
                            onClick={onCreateChapter}
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            New Chapter
                        </Button>
                        <div className="space-y-1">
                            {novel.chapters.map((chapter) => (
                                <div
                                    key={chapter.id}
                                    className="group relative"
                                >
                                    <Button
                                        variant={
                                            activeChapter?.id === chapter.id
                                                ? 'secondary'
                                                : 'ghost'
                                        }
                                        className="w-full justify-start pr-16"
                                        onClick={() => onChapterSelect(chapter)}
                                    >
                                        <span className="truncate">
                                            {chapter.title}
                                        </span>
                                    </Button>
                                    <div className="absolute top-1/2 right-2 -translate-y-1/2 text-xs text-muted-foreground">
                                        {chapter.word_count.toLocaleString()}
                                    </div>
                                </div>
                            ))}
                            {novel.chapters.length === 0 && (
                                <div className="py-4 text-center text-sm text-muted-foreground">
                                    No chapters yet.
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {activeTab === 'characters' && (
                    <div className="space-y-2">
                        <Button
                            className="w-full"
                            size="sm"
                            variant="outline"
                            onClick={() => onManageCharacter()}
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            Add Character
                        </Button>
                        <div className="space-y-1">
                            {novel.characters?.map((char) => (
                                <div
                                    key={char.id}
                                    className="group flex items-center justify-between rounded px-2 py-1 text-sm hover:bg-accent/50"
                                >
                                    <div
                                        className="flex cursor-pointer items-center gap-2 truncate"
                                        onClick={() => onManageCharacter(char)}
                                    >
                                        <User className="h-3 w-3" />
                                        <span>{char.name}</span>
                                    </div>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        className="h-4 w-4 opacity-0 group-hover:opacity-100"
                                        onClick={() => onDeleteCharacter(char)}
                                    >
                                        <Trash2 className="h-3 w-3 text-destructive" />
                                    </Button>
                                </div>
                            ))}
                            {(!novel.characters ||
                                novel.characters.length === 0) && (
                                <div className="py-4 text-center text-sm text-muted-foreground">
                                    No characters.
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {activeTab === 'locations' && (
                    <div className="space-y-2">
                        <Button
                            className="w-full"
                            size="sm"
                            variant="outline"
                            onClick={() => onManageLocation()}
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            Add Location
                        </Button>
                        <div className="space-y-1">
                            {novel.locations?.map((loc) => (
                                <div
                                    key={loc.id}
                                    className="group flex items-center justify-between rounded px-2 py-1 text-sm hover:bg-accent/50"
                                >
                                    <div
                                        className="flex cursor-pointer items-center gap-2 truncate"
                                        onClick={() => onManageLocation(loc)}
                                    >
                                        <MapPin className="h-3 w-3" />
                                        <span>{loc.name}</span>
                                    </div>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        className="h-4 w-4 opacity-0 group-hover:opacity-100"
                                        onClick={() => onDeleteLocation(loc)}
                                    >
                                        <Trash2 className="h-3 w-3 text-destructive" />
                                    </Button>
                                </div>
                            ))}
                            {(!novel.locations ||
                                novel.locations.length === 0) && (
                                <div className="py-4 text-center text-sm text-muted-foreground">
                                    No locations.
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {activeTab === 'sources' && (
                    <div className="space-y-2">
                        <div className="flex gap-2">
                            <input
                                type="file"
                                ref={fileInputRef}
                                className="hidden"
                                onChange={onUploadDocument}
                                accept=".pdf,.txt,.md,.csv"
                                multiple
                            />
                            <Button
                                className="flex-1"
                                size="sm"
                                variant="outline"
                                onClick={() => fileInputRef.current?.click()}
                            >
                                <Upload className="mr-2 h-4 w-4" />
                                Upload File
                            </Button>
                            <Dialog>
                                <DialogTrigger asChild>
                                    <Button
                                        size="icon"
                                        variant="outline"
                                        className="h-9 w-9"
                                    >
                                        <Globe className="h-4 w-4" />
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>Add Web Link</DialogTitle>
                                    </DialogHeader>
                                    <form
                                        onSubmit={(e) => {
                                            e.preventDefault();
                                            const formData = new FormData(
                                                e.currentTarget,
                                            );
                                            const url = formData.get(
                                                'url',
                                            ) as string;
                                            if (url) {
                                                onAddLink(url);
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
                        <div className="space-y-1">
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
                    </div>
                )}
            </div>
        </div>
    );
}
