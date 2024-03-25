import type { Meta, StoryObj } from '@storybook/react';

import UiFooter from './UiFooter';

const meta: Meta<typeof UiFooter> = {
  title: 'UiComponents/UiFooter',
  component: UiFooter,
  tags: ['autodocs'],
};

export default meta;

type Story = StoryObj<typeof UiFooter>;

export const Footer: Story = {};
