import type { StorybookConfig } from '@storybook/nextjs';

const toPath = 'src/assets/fonts';
const fromPath = `../${toPath}`;

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
      from: `${fromPath}/Golos/GolosText-Black.ttf`,
      to: `${toPath}/Golos/GolosText-Black.ttf`,
    },
    {
      from: `${fromPath}/Golos/GolosText-Bold.ttf`,
      to: `${toPath}/Golos/GolosText-Bold.ttf`,
    },
    {
      from: `${fromPath}/Golos/GolosText-ExtraBold.ttf`,
      to: `${toPath}/Golos/GolosText-ExtraBold.ttf`,
    },
    {
      from: `${fromPath}/Golos/GolosText-Medium.ttf`,
      to: `${toPath}/Golos/GolosText-Medium.ttf`,
    },
    {
      from: `${fromPath}/Golos/GolosText-Regular.ttf`,
      to: `${toPath}/Golos/GolosText-Regular.ttf`,
    },
    {
      from: `${fromPath}/Golos/GolosText-SemiBold.ttf`,
      to: `${toPath}/Golos/GolosText-SemiBold.ttf`,
    },
    {
      from: `${fromPath}/Inter/Inter-Bold.ttf`,
      to: `${toPath}/Inter/Inter-Bold.ttf`,
    },
    {
      from: `${fromPath}/Inter/Inter-Medium.ttf`,
      to: `${toPath}/Inter/Inter-Medium.ttf`,
    },
    {
      from: `${fromPath}/Inter/Inter-Regular.ttf`,
      to: `${toPath}/Inter/Inter-Regular.ttf`,
    },
  ],
};
export default config;
