// Gradient variations inspired by the provided complex background
export const GRADIENTS_2 = () => [
    // Variation 1: blue/purple/cyan, bottom-left dark
    `radial-gradient(circle at 60% 60%, rgba(30, 64, 175, 0.7), rgba(30, 64, 175, 0) 50%),
             radial-gradient(circle at 20% 30%, rgba(76, 29, 149, 0.6), rgba(76, 29, 149, 0) 45%),
             radial-gradient(circle at 70% 20%, rgba(6, 182, 212, 0.5), rgba(6, 182, 212, 0) 55%),
             linear-gradient(to bottom left, rgba(17, 24, 39, 1), rgba(30, 41, 59, 1))`,

    // Variation 2: swap blue and purple, top-right dark
    `radial-gradient(circle at 40% 40%, rgba(76, 29, 149, 0.7), rgba(76, 29, 149, 0) 50%),
             radial-gradient(circle at 80% 20%, rgba(30, 64, 175, 0.6), rgba(30, 64, 175, 0) 45%),
             radial-gradient(circle at 30% 70%, rgba(6, 182, 212, 0.5), rgba(6, 182, 212, 0) 55%),
             linear-gradient(to top right, rgba(17, 24, 39, 1), rgba(30, 41, 59, 1))`,

    // Variation 3: cyan focus, top-left dark
    `radial-gradient(circle at 70% 30%, rgba(6, 182, 212, 0.7), rgba(6, 182, 212, 0) 50%),
             radial-gradient(circle at 30% 60%, rgba(30, 64, 175, 0.6), rgba(30, 64, 175, 0) 45%),
             radial-gradient(circle at 60% 80%, rgba(76, 29, 149, 0.5), rgba(76, 29, 149, 0) 55%),
             linear-gradient(to top left, rgba(17, 24, 39, 1), rgba(30, 41, 59, 1))`,

    // Variation 4: more purple, bottom-right dark
    `radial-gradient(circle at 50% 70%, rgba(76, 29, 149, 0.7), rgba(76, 29, 149, 0) 50%),
             radial-gradient(circle at 80% 40%, rgba(6, 182, 212, 0.6), rgba(6, 182, 212, 0) 45%),
             radial-gradient(circle at 20% 20%, rgba(30, 64, 175, 0.5), rgba(30, 64, 175, 0) 55%),
             linear-gradient(to bottom right, rgba(17, 24, 39, 1), rgba(30, 41, 59, 1))`,

    // Variation 5: blue/cyan, left dark
    `radial-gradient(circle at 60% 40%, rgba(30, 64, 175, 0.7), rgba(30, 64, 175, 0) 50%),
             radial-gradient(circle at 30% 80%, rgba(6, 182, 212, 0.6), rgba(6, 182, 212, 0) 45%),
             radial-gradient(circle at 80% 30%, rgba(76, 29, 149, 0.5), rgba(76, 29, 149, 0) 55%),
             linear-gradient(to left, rgba(17, 24, 39, 1), rgba(30, 41, 59, 1))`,

    // Variation 6: purple/cyan, right dark
    `radial-gradient(circle at 40% 60%, rgba(76, 29, 149, 0.7), rgba(76, 29, 149, 0) 50%),
             radial-gradient(circle at 70% 20%, rgba(6, 182, 212, 0.6), rgba(6, 182, 212, 0) 45%),
             radial-gradient(circle at 20% 70%, rgba(30, 64, 175, 0.5), rgba(30, 64, 175, 0) 55%),
             linear-gradient(to right, rgba(17, 24, 39, 1), rgba(30, 41, 59, 1))`,
];

export const GRADIENTS_1 = () => [
    'linear-gradient(45deg, rgb(155 89 182) 0%, rgb(231 76 60) 45%, rgb(52 152 219) 100%)',
    'linear-gradient(60deg, rgb(52 152 219) 0%, rgb(155 89 182) 40%, rgb(231 76 60) 100%)',
    'linear-gradient(35deg, rgb(231 76 60) 0%, rgb(155 89 182) 55%, rgb(52 152 219) 100%)',
    'linear-gradient(50deg, rgb(155 89 182) 0%, rgb(52 152 219) 55%, rgb(231 76 60) 100%)',
    'linear-gradient(45deg, rgb(52 152 219) 0%, rgb(231 76 60) 50%, rgb(155 89 182) 100%)',
    'linear-gradient(70deg, rgb(231 76 60) 0%, rgb(52 152 219) 50%, rgb(155 89 182) 100%)',
];
