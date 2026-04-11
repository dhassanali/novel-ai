import { check } from '@/actions/App/Http/Controllers/ConsistencyController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ConsistencyIssue, ConsistencyReport, Novel } from '@/types/novel';
import axios from 'axios';
import { AlertTriangle, CheckCircle, ShieldAlert, ShieldCheck } from 'lucide-react';
import { useState } from 'react';

interface ConsistencyModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    novel: Novel;
}

const SEVERITY_STYLES: Record<ConsistencyIssue['severity'], string> = {
    high: 'border-destructive/50 bg-destructive/5 text-destructive',
    medium: 'border-yellow-500/50 bg-yellow-500/5 text-yellow-700 dark:text-yellow-400',
    low: 'border-muted bg-muted/30 text-muted-foreground',
};

const SEVERITY_ICONS: Record<ConsistencyIssue['severity'], typeof ShieldAlert> = {
    high: ShieldAlert,
    medium: AlertTriangle,
    low: ShieldCheck,
};

const CATEGORY_LABELS: Record<ConsistencyIssue['category'], string> = {
    character: 'Character',
    plot: 'Plot',
    timeline: 'Timeline',
    world: 'World-building',
};

export default function ConsistencyModal({
    open,
    onOpenChange,
    novel,
}: ConsistencyModalProps) {
    const [checking, setChecking] = useState(false);
    const [report, setReport] = useState<ConsistencyReport | null>(null);
    const [error, setError] = useState('');

    const runCheck = async () => {
        setChecking(true);
        setReport(null);
        setError('');

        try {
            const response = await axios.post(check.url({ novel: novel.id }));
            setReport(response.data);
        } catch {
            setError('The consistency check failed. Please try again.');
        } finally {
            setChecking(false);
        }
    };

    const handleOpenChange = (value: boolean) => {
        if (!checking) {
            onOpenChange(value);
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-h-[85vh] max-w-xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <ShieldCheck className="h-4 w-4" />
                        Story Consistency Check
                    </DialogTitle>
                </DialogHeader>

                {!report && !checking && (
                    <div className="py-4 text-center space-y-3">
                        <p className="text-sm text-muted-foreground">
                            AI will review all your chapters against character profiles, relationships, and world-building notes to find inconsistencies, plot holes, and continuity errors.
                        </p>
                        {error && (
                            <p className="text-sm text-destructive">{error}</p>
                        )}
                    </div>
                )}

                {checking && (
                    <div className="py-8 text-center space-y-3">
                        <div className="animate-pulse text-sm text-muted-foreground">
                            Analysing your novel for inconsistencies...
                        </div>
                        <p className="text-xs text-muted-foreground">
                            This may take a moment depending on the length of your novel.
                        </p>
                    </div>
                )}

                {report && (
                    <div className="space-y-4">
                        <div className="flex items-center gap-2 rounded-md border p-3">
                            {report.issues.length === 0 ? (
                                <CheckCircle className="h-4 w-4 text-green-500 shrink-0" />
                            ) : (
                                <AlertTriangle className="h-4 w-4 text-yellow-500 shrink-0" />
                            )}
                            <p className="text-sm">{report.summary}</p>
                        </div>

                        {report.issues.length > 0 && (
                            <ul className="space-y-2">
                                {report.issues.map((issue, i) => {
                                    const Icon = SEVERITY_ICONS[issue.severity];
                                    return (
                                        <li
                                            key={i}
                                            className={`rounded-md border p-3 text-sm ${SEVERITY_STYLES[issue.severity]}`}
                                        >
                                            <div className="flex items-start gap-2">
                                                <Icon className="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                                <div>
                                                    <span className="font-semibold capitalize">
                                                        {CATEGORY_LABELS[issue.category]}
                                                    </span>
                                                    <span className="mx-1.5 text-xs opacity-60">·</span>
                                                    <span className="text-xs opacity-60 capitalize">
                                                        {issue.severity}
                                                    </span>
                                                    <p className="mt-0.5 opacity-90">{issue.description}</p>
                                                </div>
                                            </div>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </div>
                )}

                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        disabled={checking}
                    >
                        Close
                    </Button>
                    <Button onClick={runCheck} disabled={checking}>
                        {checking ? 'Checking...' : report ? 'Run Again' : 'Run Check'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
