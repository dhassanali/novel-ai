import {
    destroyRelationship,
    storeRelationship,
} from '@/actions/App/Http/Controllers/CharacterController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Character, CharacterRelationship, Novel, RelationshipType } from '@/types/novel';
import { router } from '@inertiajs/react';
import { MessageSquare, Trash2, UserPlus } from 'lucide-react';
import { useEffect, useState } from 'react';

const RELATIONSHIP_TYPES: { value: RelationshipType; label: string }[] = [
    { value: 'friend', label: 'Friend' },
    { value: 'enemy', label: 'Enemy' },
    { value: 'lover', label: 'Lover' },
    { value: 'family', label: 'Family' },
    { value: 'rival', label: 'Rival' },
    { value: 'mentor', label: 'Mentor' },
    { value: 'ally', label: 'Ally' },
];

interface CharacterModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    character: Character | null;
    novel: Novel;
    onSave: (character: Partial<Character>) => void;
    onOpenChat: (character: Character) => void;
}

export default function CharacterModal({
    open,
    onOpenChange,
    character,
    novel,
    onSave,
    onOpenChat,
}: CharacterModalProps) {
    const [name, setName] = useState('');
    const [role, setRole] = useState('');
    const [description, setDescription] = useState('');
    const [personalityTraits, setPersonalityTraits] = useState('');
    const [backstory, setBackstory] = useState('');
    const [goals, setGoals] = useState('');
    const [speechPatterns, setSpeechPatterns] = useState('');

    // Relationship form state
    const [relatedCharacterId, setRelatedCharacterId] = useState('');
    const [relationshipType, setRelationshipType] = useState<RelationshipType>('friend');
    const [relationshipDesc, setRelationshipDesc] = useState('');
    const [addingRelationship, setAddingRelationship] = useState(false);

    useEffect(() => {
        if (character) {
            setName(character.name);
            setRole(character.role ?? '');
            setDescription(character.description ?? '');
            setPersonalityTraits(character.personality_traits ?? '');
            setBackstory(character.backstory ?? '');
            setGoals(character.goals ?? '');
            setSpeechPatterns(character.speech_patterns ?? '');
        } else {
            setName('');
            setRole('');
            setDescription('');
            setPersonalityTraits('');
            setBackstory('');
            setGoals('');
            setSpeechPatterns('');
        }
        setRelatedCharacterId('');
        setRelationshipType('friend');
        setRelationshipDesc('');
        setAddingRelationship(false);
    }, [character, open]);

    const handleSave = () => {
        onSave({
            name,
            role,
            description,
            personality_traits: personalityTraits,
            backstory,
            goals,
            speech_patterns: speechPatterns,
        });
    };

    const handleAddRelationship = () => {
        if (!character || !relatedCharacterId) {
            return;
        }
        setAddingRelationship(true);
        router.post(
            storeRelationship.url({ novel: novel.id, character: character.id }),
            {
                related_character_id: parseInt(relatedCharacterId),
                type: relationshipType,
                description: relationshipDesc || null,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    setAddingRelationship(false);
                    setRelatedCharacterId('');
                    setRelationshipDesc('');
                },
            },
        );
    };

    const handleDeleteRelationship = (rel: CharacterRelationship) => {
        if (!character) {
            return;
        }
        router.delete(
            destroyRelationship.url({
                novel: novel.id,
                character: character.id,
                relationship: rel.id,
            }),
            { preserveScroll: true },
        );
    };

    const otherCharacters = novel.characters.filter((c) => c.id !== character?.id);
    const currentRelationshipIds = new Set(
        character?.relationships?.map((r) => r.related_character_id) ?? [],
    );
    const availableCharacters = otherCharacters.filter(
        (c) => !currentRelationshipIds.has(c.id),
    );

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] max-w-xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {character ? 'Edit Character' : 'Add Character'}
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-6">
                    {/* Core Info */}
                    <section className="space-y-3">
                        <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                            Core Info
                        </h3>
                        <div>
                            <Label htmlFor="char-name">Name</Label>
                            <Input
                                id="char-name"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                placeholder="Character Name"
                            />
                        </div>
                        <div>
                            <Label htmlFor="char-role">Role</Label>
                            <Input
                                id="char-role"
                                value={role}
                                onChange={(e) => setRole(e.target.value)}
                                placeholder="Protagonist, Antagonist, etc."
                            />
                        </div>
                        <div>
                            <Label htmlFor="char-desc">Description</Label>
                            <Textarea
                                id="char-desc"
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                                placeholder="Physical appearance, overview..."
                                rows={2}
                            />
                        </div>
                    </section>

                    {/* Personality Profile */}
                    <section className="space-y-3">
                        <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                            Personality Profile
                        </h3>
                        <div>
                            <Label htmlFor="char-personality">Personality Traits</Label>
                            <Textarea
                                id="char-personality"
                                value={personalityTraits}
                                onChange={(e) => setPersonalityTraits(e.target.value)}
                                placeholder="Bold, sarcastic, deeply curious..."
                                rows={2}
                            />
                        </div>
                        <div>
                            <Label htmlFor="char-backstory">Backstory</Label>
                            <Textarea
                                id="char-backstory"
                                value={backstory}
                                onChange={(e) => setBackstory(e.target.value)}
                                placeholder="Where they came from, formative events..."
                                rows={2}
                            />
                        </div>
                        <div>
                            <Label htmlFor="char-goals">Goals & Motivations</Label>
                            <Textarea
                                id="char-goals"
                                value={goals}
                                onChange={(e) => setGoals(e.target.value)}
                                placeholder="What they want and why..."
                                rows={2}
                            />
                        </div>
                        <div>
                            <Label htmlFor="char-speech">Speech Patterns</Label>
                            <Textarea
                                id="char-speech"
                                value={speechPatterns}
                                onChange={(e) => setSpeechPatterns(e.target.value)}
                                placeholder="Short sentences, rhetorical questions, avoids small talk..."
                                rows={2}
                            />
                        </div>
                    </section>

                    {/* Relationships — only when editing an existing character */}
                    {character && (
                        <section className="space-y-3">
                            <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                                Relationships
                            </h3>

                            {character.relationships && character.relationships.length > 0 ? (
                                <ul className="space-y-2">
                                    {character.relationships.map((rel) => (
                                        <li
                                            key={rel.id}
                                            className="flex items-start justify-between gap-2 rounded-md border px-3 py-2 text-sm"
                                        >
                                            <div>
                                                <span className="font-medium">
                                                    {rel.related_character.name}
                                                </span>
                                                <span className="ml-2 rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground capitalize">
                                                    {rel.type}
                                                </span>
                                                {rel.description && (
                                                    <p className="mt-0.5 text-muted-foreground">
                                                        {rel.description}
                                                    </p>
                                                )}
                                            </div>
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                className="h-6 w-6 shrink-0"
                                                onClick={() => handleDeleteRelationship(rel)}
                                            >
                                                <Trash2 className="h-3 w-3 text-destructive" />
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-muted-foreground">No relationships yet.</p>
                            )}

                            {availableCharacters.length > 0 && (
                                <div className="space-y-2 rounded-md border p-3">
                                    <p className="text-xs font-medium text-muted-foreground">Add Relationship</p>
                                    <Select value={relatedCharacterId} onValueChange={setRelatedCharacterId}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select character..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableCharacters.map((c) => (
                                                <SelectItem key={c.id} value={String(c.id)}>
                                                    {c.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <Select value={relationshipType} onValueChange={(v) => setRelationshipType(v as RelationshipType)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {RELATIONSHIP_TYPES.map((t) => (
                                                <SelectItem key={t.value} value={t.value}>
                                                    {t.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <Input
                                        value={relationshipDesc}
                                        onChange={(e) => setRelationshipDesc(e.target.value)}
                                        placeholder="Optional description..."
                                    />
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        className="w-full"
                                        disabled={!relatedCharacterId || addingRelationship}
                                        onClick={handleAddRelationship}
                                    >
                                        <UserPlus className="mr-2 h-3 w-3" />
                                        Add Relationship
                                    </Button>
                                </div>
                            )}
                        </section>
                    )}
                </div>

                <DialogFooter className="flex-col gap-2 sm:flex-row">
                    {character && (
                        <Button
                            variant="outline"
                            onClick={() => {
                                onOpenChange(false);
                                onOpenChat(character);
                            }}
                        >
                            <MessageSquare className="mr-2 h-4 w-4" />
                            Talk to Character
                        </Button>
                    )}
                    <Button onClick={handleSave}>Save</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
