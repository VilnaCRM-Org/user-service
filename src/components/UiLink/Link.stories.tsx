import type { Meta, StoryObj } from '@storybook/react';

import UiLink from './index';

const meta: Meta<typeof UiLink> = {
  title: 'UiComponents/UiLink',
  component: UiLink,
  tags: ['autodocs'],
  argTypes: {
    children: {
      type: 'string',
      description: 'Text for the link',
    },
    href: {
      type: 'string',
      description: 'Link URL',
    },
  },
};

export default meta;

type Story = StoryObj<typeof UiLink>;

export const Link: Story = {
  args: {
    children: 'Link',
    href: '/',
  },
};
