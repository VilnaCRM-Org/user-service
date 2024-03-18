import type { StorybookConfig } from '@storybook/nextjs';

const config: StorybookConfig = {
  stories: ['../src/**/*.mdx', '../src/**/*.stories.@(js|jsx|mjs|ts|tsx)'],
  addons: [
    '@storybook/addon-links',
    '@storybook/addon-essentials',
    '@storybook/addon-onboarding',
    '@storybook/addon-interactions',
  ],
  framework: {
    name: '@storybook/nextjs',
    options: {},
  },
  docs: {
    autodocs: 'tag',
  },
  staticDirs: [
    {
      from: '../src/features/landing/assets/fonts/Golos/GolosText-Black.ttf',
      to: 'src/features/landing/assets/fonts/Golos/GolosText-Black.ttf',
    },
    {
      from: '../src/features/landing/assets/fonts/Golos/GolosText-Bold.ttf',
      to: 'src/features/landing/assets/fonts/Golos/GolosText-Bold.ttf',
    },
    {
      from: '../src/features/landing/assets/fonts/Golos/GolosText-ExtraBold.ttf',
      to: 'src/features/landing/assets/fonts/Golos/GolosText-ExtraBold.ttf',
    },
    {
      from: '../src/features/landing/assets/fonts/Golos/GolosText-Medium.ttf',
      to: 'src/features/landing/assets/fonts/Golos/GolosText-Medium.ttf',
    },
    {
      from: '../src/features/landing/assets/fonts/Golos/GolosText-Regular.ttf',
      to: 'src/features/landing/assets/fonts/Golos/GolosText-Regular.ttf',
    },
    {
      from: '../src/features/landing/assets/fonts/Golos/GolosText-SemiBold.ttf',
      to: 'src/features/landing/assets/fonts/Golos/GolosText-SemiBold.ttf',
    },
    {
      from: '../src/features/landing/assets/fonts/Inter/Inter-Bold.ttf',
      to: 'src/features/landing/assets/fonts/Inter/Inter-Bold.ttf',
    },
    {
      from: '../src/features/landing/assets/fonts/Inter/Inter-Medium.ttf',
      to: 'src/features/landing/assets/fonts/Inter/Inter-Medium.ttf',
    },
    {
      from: '../src/features/landing/assets/fonts/Inter/Inter-Regular.ttf',
      to: 'src/features/landing/assets/fonts/Inter/Inter-Regular.ttf',
    },
  ],
};
export default config;
