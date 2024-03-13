import type { Meta, StoryObj } from '@storybook/react';

import testImage from '../../assets/svg/TooltipIcons/Joomla.svg';

import UiImage from './index';

const meta: Meta<typeof UiImage> = {
  title: 'UiComponents/UiImage',
  component: UiImage,
  tags: ['autodocs'],
  argTypes: {
    src: {
      control: 'text',
      description: 'Image source URL',
    },
    alt: {
      control: 'text',
      description: 'Alternative text for the image',
    },
    sx: {
      control: 'object',
      description: 'Style object for the image',
    },
  },
};

export default meta;

type Story = StoryObj<typeof UiImage>;

export const StoryImage: Story = {
  args: {
    src: testImage.src,
    alt: 'Story example image',
    sx: {
      width: '200px',
      height: '200px',
    },
  },
};
