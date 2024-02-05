import type { Meta, StoryObj } from '@storybook/react';

import UiToolbar from './index';

const meta: Meta<typeof UiToolbar> = {
  title: 'UiComponents/UiToolbar',
  component: UiToolbar,
  tags: ['autodocs'],
  argTypes: {},
};

export default meta;

type Story = StoryObj<typeof UiToolbar>;

export const Toolbar: Story = {
  args: {},
};
