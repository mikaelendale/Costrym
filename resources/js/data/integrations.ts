export interface Integration {
    name: string;
    icon: string;
    description: string;
    connected: boolean;
}

export const integrations: Integration[] = [
    {
        name: 'Slack',
        icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/slack/slack-original.svg',
        description: 'Connect to Slack for us to get, send, and read the messages in your workspace.',
        connected: true,
    },
    {
        name: 'GitHub',
        icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/github/github-original.svg',
        description: 'Integrate with GitHub to manage repositories, issues, and pull requests.',
        connected: false,
    },
    {
        name: 'Trello',
        icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/trello/trello-plain.svg',
        description: 'Connect to Trello to organize and manage your boards and cards.',
        connected: true,
    },
    {
        name: 'Google Drive',
        icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/google/google-original.svg',
        description: 'Access and manage your files directly from Google Drive.',
        connected: false,
    },
    {
        name: 'Asana',
        icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/asana/asana-original.svg',
        description: 'Integrate with Asana to track your tasks and projects efficiently.',
        connected: true,
    },
    {
        name: 'Dropbox',
        icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/dropbox/dropbox-original.svg',
        description: 'Sync and manage your files with Dropbox integration.',
        connected: false,
    },
    {
        name: 'Jira',
        icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/jira/jira-original.svg',
        description: 'Integrate with Jira to manage issues and track project progress.',
        connected: true,
    },
    {
        name: 'Notion',
        icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/notion/notion-original.svg',
        description: 'Connect to Notion to organize notes, tasks, and projects.',
        connected: false,
    },
    {
        name: 'Zoom',
        icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/zoom/zoom-original.svg',
        description: 'Schedule and join meetings directly with Zoom integration.',
        connected: true,
    },
    {
        name: 'Microsoft Teams',
        icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/microsoftteams/microsoftteams-original.svg',
        description: 'Collaborate and communicate with your team using Microsoft Teams.',
        connected: false,
    },
];
