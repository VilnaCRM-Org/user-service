import { Stack } from '@mui/material';
import { Meta, StoryObj } from '@storybook/react';
import { t } from 'i18next';
import { FieldValues, useForm } from 'react-hook-form';

import UiButton from '../UiButton';

import { CustomTextField } from './types';

import UiTextFieldForm from './index';

const meta: Meta<typeof UiTextFieldForm> = {
  title: 'UiComponents/UiTextFieldForm',
  component: UiTextFieldForm,
  tags: ['autodocs'],
  argTypes: {
    name: {
      control: { type: 'text' },
      defaultValue: 'FullName',
    },
    type: {
      control: { type: 'radio' },
      options: ['text', 'email', 'password'],
      defaultValue: 'text',
    },
    rules: {
      control: { type: 'object' },
      defaultValue: {
        required: 'This field is required',
      },
    },
    placeholder: {
      control: { type: 'text' },
      defaultValue: 'Enter text...',
    },
    fullWidth: {
      control: { type: 'boolean' },
      defaultValue: false,
    },
  },
};

export default meta;

type Story = StoryObj<typeof UiTextFieldForm>;

function TextFieldFormStory<T extends FieldValues>(args: CustomTextField<T>): React.ReactElement {
  const { handleSubmit, control } = useForm<{ FullName: string }>({
    mode: 'onTouched',
  });

  const { rules, placeholder, type, fullWidth } = args;

  return (
    <form onSubmit={handleSubmit(() => {})}>
      <Stack direction="row" alignItems="center" gap="1rem">
        <UiTextFieldForm
          control={control}
          rules={rules}
          placeholder={placeholder}
          type={type}
          name="FullName"
          fullWidth={fullWidth}
        />
        <UiButton size="small" variant="contained" type="submit">
          {t('Submit')}
        </UiButton>
      </Stack>
    </form>
  );
}

export const TextFieldForm: Story = {
  render: args => <TextFieldFormStory {...args} />,
  args: {
    rules: {
      required: t('This field is required'),
      validate: (value: string) => {
        if (value.length < 3) {
          return t('Name must be at least 3 characters');
        }
        return true;
      },
    },
    type: 'text',
    placeholder: t('Enter text...'),
    fullWidth: false,
  },
};
