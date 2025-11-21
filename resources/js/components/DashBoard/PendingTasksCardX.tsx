import { useState } from 'react';
import { router } from '@inertiajs/react';

interface Task {
    id: number;
    agent_name: string;
    status: string;
    priority: number;
    data: {
        name: string;
        description: string;
        task_type: string;
        estimated_savings?: string;
        schedule?: string;
        metadata?: any;
    };
    created_at: string;
}

interface PendingTasksCardProps {
    tasks: Task[];
}

export default function PendingTasksCard({ tasks }: PendingTasksCardProps) {
    const [selectedTask, setSelectedTask] = useState<Task | null>(null);
    const [processing, setProcessing] = useState<number | null>(null);

    const handleApprove = (taskId: number) => {
        setProcessing(taskId);
        router.post(
            `/tasks/${taskId}/approve`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedTask(null);
                    setProcessing(null);
                },
                onError: () => {
                    setProcessing(null);
                },
            }
        );
    };

    const handleReject = (taskId: number) => {
        setProcessing(taskId);
        router.post(
            `/tasks/${taskId}/reject`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedTask(null);
                    setProcessing(null);
                },
                onError: () => {
                    setProcessing(null);
                },
            }
        );
    };

    const getTaskTypeLabel = (type: string) => {
        return type === 'one_time' ? 'One-time Task' : 'Recurring Task';
    };

    const getTaskTypeBadge = (type: string) => {
        return type === 'one_time'
            ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
            : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300';
    };

    const getPriorityBadge = (priority: number) => {
        if (priority === 1) return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        if (priority === 2) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
    };

    const getPriorityLabel = (priority: number) => {
        if (priority === 1) return 'High Priority';
        if (priority === 2) return 'Medium Priority';
        return 'Low Priority';
    };

    if (!tasks || tasks.length === 0) {
        return (
            <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        🤖 AI-Generated Tasks
                    </h3>
                    <span className="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                        0 Pending
                    </span>
                </div>
                <div className="text-center py-8">
                    <div className="text-gray-400 dark:text-gray-600 mb-2">
                        <svg className="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        No pending tasks. The AI will generate cost-saving tasks based on your financial data.
                    </p>
                </div>
            </div>
        );
    }

    return (
        <>
            <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        🤖 AI-Generated Tasks
                    </h3>
                    <span className="px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                        {tasks.length} Awaiting Approval
                    </span>
                </div>

                <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Review and approve AI-generated cost-saving tasks before execution
                </p>

                <div className="space-y-3">
                    {tasks.map((task) => (
                        <div
                            key={task.id}
                            className="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-gray-300 dark:hover:border-gray-600 transition-colors cursor-pointer"
                            onClick={() => setSelectedTask(task)}
                        >
                            <div className="flex items-start justify-between">
                                <div className="flex-1">
                                    <div className="flex items-center gap-2 mb-2">
                                        <span className={`px-2 py-0.5 text-xs font-medium rounded ${getPriorityBadge(task.priority)}`}>
                                            Priority {task.priority}
                                        </span>
                                        <span className={`px-2 py-0.5 text-xs font-medium rounded ${getTaskTypeBadge(task.data.task_type)}`}>
                                            {getTaskTypeLabel(task.data.task_type)}
                                        </span>
                                    </div>
                                    <h4 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                        {task.data.name}
                                    </h4>
                                    <p className="text-xs text-gray-600 dark:text-gray-400 line-clamp-2">
                                        {task.data.description}
                                    </p>
                                    {task.data.estimated_savings && (
                                        <div className="mt-2 flex items-center gap-2">
                                            <span className="text-xs font-semibold text-green-600 dark:text-green-400">
                                                💰 {task.data.estimated_savings}
                                            </span>
                                            <span className="text-xs text-gray-500 dark:text-gray-500">
                                                • Agent: Auto-selected
                                            </span>
                                        </div>
                                    )}
                                </div>
                                <button
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        setSelectedTask(task);
                                    }}
                                    className="ml-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                >
                                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Task Detail Modal */}
            {selectedTask && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50" onClick={() => setSelectedTask(null)}>
                    <div className="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto" onClick={(e) => e.stopPropagation()}>
                        {/* Modal Header */}
                        <div className="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                            <div className="flex items-center justify-between">
                                <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    Task Review
                                </h2>
                                <button
                                    onClick={() => setSelectedTask(null)}
                                    className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                >
                                    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {/* Modal Body */}
                        <div className="px-6 py-4">
                            {/* Priority & Type Badges */}
                            <div className="flex items-center gap-2 mb-4">
                                <span className={`px-3 py-1 text-xs font-medium rounded ${getPriorityBadge(selectedTask.priority)}`}>
                                    {getPriorityLabel(selectedTask.priority)}
                                </span>
                                <span className={`px-3 py-1 text-xs font-medium rounded ${getTaskTypeBadge(selectedTask.data.task_type)}`}>
                                    {getTaskTypeLabel(selectedTask.data.task_type)}
                                </span>
                            </div>

                            {/* Task Name */}
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                {selectedTask.data.name}
                            </h3>

                            {/* Task Description */}
                            <div className="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-4">
                                <p className="text-sm text-gray-700 dark:text-gray-300">
                                    {selectedTask.data.description}
                                </p>
                            </div>

                            {/* Task Details Grid */}
                            <div className="grid grid-cols-2 gap-4 mb-4">
                                <div className="border border-blue-200 dark:border-blue-900 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                                    <div className="text-xs text-blue-600 dark:text-blue-400 mb-1">AI Agent</div>
                                    <div className="text-sm font-medium text-blue-900 dark:text-blue-100">
                                        Auto-Selected 🤖
                                    </div>
                                    <div className="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                        Best agent chosen at execution
                                    </div>
                                </div>

                                {selectedTask.data.estimated_savings && (
                                    <div className="border border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                                        <div className="text-xs text-green-600 dark:text-green-400 mb-1">Estimated Savings</div>
                                        <div className="text-sm font-bold text-green-700 dark:text-green-300">
                                            💰 {selectedTask.data.estimated_savings}
                                        </div>
                                    </div>
                                )}

                                {selectedTask.data.schedule && (
                                    <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                        <div className="text-xs text-gray-500 dark:text-gray-400 mb-1">Schedule</div>
                                        <div className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {selectedTask.data.schedule.replace(/_/g, ' ')}
                                        </div>
                                    </div>
                                )}

                                <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                    <div className="text-xs text-gray-500 dark:text-gray-400 mb-1">Created</div>
                                    <div className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {new Date(selectedTask.created_at).toLocaleDateString()}
                                    </div>
                                </div>
                            </div>

                            {/* AI Notice */}
                            <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-900 rounded-lg p-4 mb-4">
                                <div className="flex items-start gap-3">
                                    <div className="text-blue-500 dark:text-blue-400 mt-0.5">
                                        <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-blue-900 dark:text-blue-100 mb-1">
                                            AI-Generated Task
                                        </p>
                                        <p className="text-xs text-blue-700 dark:text-blue-300">
                                            This task was automatically generated by the Master Orchestrator AI based on your financial data and business goals. Review the details and approve if you'd like to proceed.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Modal Footer */}
                        <div className="sticky bottom-0 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                            <div className="flex items-center justify-end gap-3">
                                <button
                                    onClick={() => handleReject(selectedTask.id)}
                                    disabled={processing === selectedTask.id}
                                    className="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                >
                                    {processing === selectedTask.id ? 'Processing...' : 'Reject'}
                                </button>
                                <button
                                    onClick={() => handleApprove(selectedTask.id)}
                                    disabled={processing === selectedTask.id}
                                    className="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                >
                                    {processing === selectedTask.id ? 'Processing...' : '✓ Approve & Execute'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}

