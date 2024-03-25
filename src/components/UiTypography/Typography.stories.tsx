import type { Meta, StoryObj } from '@storybook/react';
import { t } from 'i18next';

import UiTypography from './index';

const meta: Meta<typeof UiTypography> = {
  title: 'UiComponents/UiTypography',
  component: UiTypography,
  tags: ['autodocs'],
  argTypes: {
    children: {
      type: 'string',
      description: 'Text for the typography',
    },
    variant: {
      type: 'string',
      description: 'Variant of the typography',
      options: [
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'medium16',
        'medium15',
        'medium14',
        'regular16',
        'bodyText18',
        'bodyText16',
        'bold22',
        'demi18',
        'button',
        'mobileText',
      ],
      control: { type: 'select' },
    },
  },
};

export default meta;

type Story = StoryObj<typeof UiTypography>;

export const Typography: Story = {
  args: {
    children: t('Typography'),
    variant: 'h5',
  },
};
