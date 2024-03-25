import type { Meta, StoryObj } from '@storybook/react';
import { t } from 'i18next';

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
    children: t('Link'),
    href: '/',
  },
};
